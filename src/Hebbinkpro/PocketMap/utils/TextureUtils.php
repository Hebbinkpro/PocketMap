<?php
/*
 *   _____           _        _   __  __
 *  |  __ \         | |      | | |  \/  |
 *  | |__) |__   ___| | _____| |_| \  / | __ _ _ __
 *  |  ___/ _ \ / __| |/ / _ \ __| |\/| |/ _` | '_ \
 *  | |  | (_) | (__|   <  __/ |_| |  | | (_| | |_) |
 *  |_|   \___/ \___|_|\_\___|\__|_|  |_|\__,_| .__/
 *                                            | |
 *                                            |_|
 *
 * Copyright (C) 2023 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\utils;

use GdImage;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\textures\model\BlockModel;
use Hebbinkpro\PocketMap\textures\model\BlockModels;
use Hebbinkpro\PocketMap\textures\TerrainTextures;
use Hebbinkpro\PocketMap\utils\block\BlockStateParser;
use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use Hebbinkpro\PocketMap\utils\block\OldBlockTypeNames;
use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\world\biome\Biome;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\format\Chunk;

class TextureUtils
{
    /** @var array<int, array<int, GdImage>> */
    private static array $blockTextureMap = [];

    /**
     * Get the total amount of visible blocks that can be shown on the given amount of pixels
     * @param int $pixels the amount of pixels
     * @return int the amount of blocks that fits in the pixels
     */
    public static function getTotalBlocks(int $pixels): int
    {
        $maxBlocks = 16;

        // only when the amount of pixels is smaller than 16 we will decrease the amount of visible blocks.
        if ($pixels < $maxBlocks) {
            $maxBlocks = $pixels;
        }

        return $maxBlocks;
    }

    /**
     * Get the pixel size of each block
     * @param int $totalPixels the total amount of pixels
     * @param int $blocks the total amount of blocks
     * @return int the amount of pixels each block will occupy floored
     */
    public static function getPixelsPerBlock(int $totalPixels, int $blocks): int
    {
        return (int)floor(max($totalPixels / $blocks, 1));
    }

    public static function getBlockTexture(Block $block, Chunk $chunk, TerrainTextures $terrainTextures, int $size): ?GdImage
    {
        // get the block model
        if (($model = BlockModels::getInstance()->get($block)) === null) return null;

        $differentModel = BlockUtils::hasDifferentModelForSameState($block);
        $texture = self::createBlockTexture($block, $differentModel ? null : $model, $chunk, $terrainTextures);
        if ($texture === null) return null;

        // if block can have different models for the same state, apply the model here
        if ($differentModel) {
            $modelTexture = $model->getModelTexture($block, $chunk, $texture);

            if ($modelTexture !== null) {
                imagedestroy($texture);
                $texture = $modelTexture;
            }
        }

        // resize the img
        $resized = self::getCompressedImage($texture, PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE, $size, $size);
        imagedestroy($texture);

        return $resized;
    }

    /**
     * Get a block texture as an GdImage
     * @param Block $block
     * @param BlockModel|null $model
     * @param Chunk $chunk
     * @param TerrainTextures $terrainTextures
     * @return GdImage|null the texture of the block
     */
    private static function createBlockTexture(Block $block, ?BlockModel $model, Chunk $chunk, TerrainTextures $terrainTextures): ?GdImage
    {
        $pos = $block->getPosition()->floor();

        // get the biome
        $biomeId = $chunk->getBiomeId((int)$pos->getX(), (int)$pos->getY(), (int)$pos->getZ());
        $biome = BiomeRegistry::getInstance()->getBiome($biomeId);

        if (!array_key_exists($biome->getId(), self::$blockTextureMap)) {
            self::$blockTextureMap[$biome->getId()] = [];
        }

        // texture exists in the cache, return it
        if (array_key_exists($block->getStateId(), self::$blockTextureMap[$biome->getId()])) {
            $cacheImg = self::$blockTextureMap[$biome->getId()][$block->getStateId()];

            // create image and copy the cache data to it
            $img = self::getEmptyTexture();
            if ($img === false) return null;

            imagealphablending($img, false);
            imagecopy($img, $cacheImg, 0, 0, 0, 0, PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);
            imagesavealpha($img, true);

            // return the copy of the cached image
            return $img;
        }

        if (($path = $terrainTextures->getBlockTexturePath($block)) === null) {
            // set the path to the fallback texture
            $fallbackBlock = $terrainTextures->getOptions()->getFallbackBlock();
            if ($fallbackBlock === null) $path = null;
            else $path = $terrainTextures->getBlockTexturePath($fallbackBlock);
        }

        if (is_file($path . ".png")) $img = imagecreatefrompng($path . ".png");
        else if (is_file($path . ".tga")) $img = imagecreatefromtga($path . ".tga");
        else {
            // the path is null, return empty image
            $img = self::getEmptyTexture();
        }
        if ($img === false) return null;

        imagealphablending($img, false);

        // set the model
        $modelImg = $img;
        if ($model !== null) {
            $modelImg = $model->getModelTexture($block, $chunk, $img);
            imagedestroy($img);
        }
        if ($modelImg === null) return null;

        // set the color map
        self::applyColorMap($modelImg, $block, $biome, $terrainTextures);
        imagesavealpha($modelImg, true);

        // set the rotation
        $rotatedImg = TextureUtils::rotateToFacing($modelImg, BlockStateParser::getBlockFace($block));
        if ($rotatedImg === null) return null;
        imagedestroy($modelImg);

        // create a cache image
        $cacheImg = self::getEmptyTexture();
        if ($cacheImg === false) return null;

        imagealphablending($cacheImg, false);
        imagecopy($cacheImg, $rotatedImg, 0, 0, 0, 0, PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);
        imagesavealpha($cacheImg, true);

        // store the cache image
        self::$blockTextureMap[$biome->getId()][$block->getStateId()] = $cacheImg;

        return $rotatedImg;
    }

    public static function getEmptyTexture(int $size = PocketMap::TEXTURE_SIZE): GdImage|false
    {
        $texture = imagecreatetruecolor($size, $size);
        if ($texture === false) return false;

        imagefill($texture, 0, 0, imagecolorexactalpha($texture, 0, 0, 0, 127));
        imagesavealpha($texture, true);

        return $texture;
    }

    /**
     * Apply a color map to the given block texture
     * @param GdImage $texture the texture to apply the color map on
     * @param Block $block the block of the texture
     * @param Biome $biome the biome the block is in
     * @param TerrainTextures $terrainTextures the resource pack
     * @return void
     */
    public static function applyColorMap(GdImage $texture, Block $block, Biome $biome, TerrainTextures $terrainTextures): void
    {
        // get the color from the cache
        $colorMap = ColorMapParser::getColorFromBlock($block, $biome, $terrainTextures);

        // the given block is not mapped
        if ($colorMap < 0) return;

        self::applyColorOverlay($texture, $colorMap, PocketMap::TEXTURE_SIZE);

    }

    /**
     * Apply an overlay to the image
     * @param GdImage $image the image
     * @param int $overlay the color of the overlay
     * @param int $size the size (in pixels) of the image
     * @return void
     */
    public static function applyColorOverlay(GdImage $image, int $overlay, int $size): void
    {
        $co = imagecolorsforindex($image, $overlay);

        $rm = $co["red"] / 255;
        $gm = $co["green"] / 255;
        $bm = $co["blue"] / 255;

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                if (($color = imagecolorat($image, $x, $y)) === false) continue;

                $c = imagecolorsforindex($image, $color);
                if ($c["alpha"] == 127) continue; // transparent pixel

                $cr = [
                    "red" => (int)floor($c["red"] * $rm),
                    "green" => (int)floor($c["green"] * $gm),
                    "blue" => (int)floor($c["blue"] * $bm),
                    "alpha" => $c["alpha"]
                ];

                imagesetpixel($image, $x, $y, imagecolorexactalpha($image, $cr["red"], $cr["green"], $cr["blue"], $cr["alpha"]));
            }
        }
    }

    /**
     * Rotate an image on the given axis
     * @param GdImage $image
     * @param int $facing
     * @return GdImage|null
     */
    public static function rotateToFacing(GdImage $image, int $facing): ?GdImage
    {
        $angle = match ($facing) {
            Facing::DOWN, Facing::SOUTH => 180,     // -y, +z
            Facing::EAST => 270,                    // +x
            Facing::WEST => 90,                     // -x
            default => 0                            // +y, -z
        };

        // angle of 0 does not have to be rotated
        if ($angle == 0) return $image;

        // rotate the image
        $img = imagerotate($image, $angle, 0);
        if ($img === false) return null;
        return $img;
    }

    /**
     * Get a compressed image
     * @param GdImage $src the image to compress
     * @param int $srcWidth the width of the image
     * @param int $srcHeight teh height of the image
     * @param int $newWidth the new width of the image
     * @param int $newHeight the new height of the image
     * @return GdImage|null the compressed image with the new weight and height
     */
    public static function getCompressedImage(GdImage $src, int $srcWidth, int $srcHeight, int $newWidth, int $newHeight): ?GdImage
    {
        $compressedImg = imagecreatetruecolor($newWidth, $newHeight);
        if ($compressedImg === false) return null;
        imagealphablending($compressedImg, false);
        imagecopyresized($compressedImg, $src, 0, 0, 0, 0, $newWidth, $newHeight, $srcHeight, $srcWidth);
        imagesavealpha($compressedImg, true);

        return $compressedImg;
    }

    /**
     * Apply an alpha value over the whole image
     * @param GdImage $image the image
     * @param int $alpha the alpha value
     * @param int $size the size of the image
     * @return void
     */
    public static function applyAlpha(GdImage $image, int $alpha, int $size): void
    {

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                if (($color = imagecolorat($image, $x, $y)) === false) continue;
                $c = imagecolorsforindex($image, $color);
                $c["alpha"] += $alpha;
                if ($c["alpha"] > 127) $c["alpha"] = 127;
                else if ($c["alpha"] < 0) $c["alpha"] = 0;

                imagesetpixel($image, $x, $y, imagecolorexactalpha($image, $c["red"], $c["green"], $c["blue"], $c["alpha"]));
            }
        }
    }

    /**
     * Get the texture name of a block.
     * - In MOST cases, it's just the name with minecraft: removed, or \_block\_ replaced with _.
     * - There are some exceptions in which that's not the case and this is fixed with the match.
     * @param Block $block the name of the block
     * @return string|null the texture name
     */
    public static function getBlockTextureName(Block $block): ?string
    {
        $stateData = BlockStateParser::getBlockStateData($block);
        if ($stateData === null) return null;

        $name = OldBlockTypeNames::getTypeName($stateData->getName());

        // replace _block_ with _
        return str_replace("_block_", "_", self::removeBlockTypeNamePrefix($name));
    }

    public static function removeBlockTypeNamePrefix(string $name): string
    {
        // remove prefix from the item if it exists
        if (str_contains($name, ":")) $name = explode(":", $name)[1];
        return $name;
    }

    /**
     * Clear the texture cache
     * @return void
     */
    public static function clearCache(): void
    {
        // destroy all images
        foreach (self::$blockTextureMap as $blocks) {
            foreach ($blocks as $texture) {
                imagedestroy($texture);
            }
        }

        // make the list empty
        self::$blockTextureMap = [];
    }

    /**
     * @param Block $block
     * @param list<string> $availableFaces
     * @return string|null
     */
    public static function getBlockFaceTexture(Block $block, array $availableFaces): ?string
    {
        if (sizeof($availableFaces) == 0) return null;

        $axis = BlockStateParser::getBlockFace($block);

        $faces = match ($axis) {
            Facing::UP => ["up"],
            Facing::DOWN => ["down", "up"],
            Facing::EAST => ["east", "side"],
            Facing::WEST => ["west", "side"],
            Facing::SOUTH => ["south", "side"],
            Facing::NORTH => ["north", "side"],
            default => ["up", $availableFaces[0]],
        };

        $validFaces = array_intersect($faces, $availableFaces);
        return $validFaces[array_key_first($validFaces)];
    }


}