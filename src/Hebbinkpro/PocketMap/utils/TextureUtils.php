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
 * Copyright (c) 2024-2025 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\utils;

use GdImage;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\textures\model\BlockModelInterface;
use Hebbinkpro\PocketMap\textures\model\BlockModels;
use Hebbinkpro\PocketMap\textures\TerrainTextures;
use Hebbinkpro\PocketMap\utils\block\BlockStateParser;
use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use Hebbinkpro\PocketMap\utils\block\OldBlockTypeNames;
use pocketmine\block\Bed;
use pocketmine\block\BigDripleafHead;
use pocketmine\block\BigDripleafStem;
use pocketmine\block\Block;
use pocketmine\block\Campfire;
use pocketmine\block\SmallDripleaf;
use pocketmine\block\SoulCampfire;
use pocketmine\math\Facing;
use pocketmine\world\biome\Biome;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\format\Chunk;

/**
 * TODO use entity textures for tile blocks, e.g. chests, signs and beds
 */
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

        $texture = self::createBlockTexture($block, $model, $chunk, $terrainTextures);
        if ($texture === null) return null;

        // resize the img
        return self::getCompressedImage($texture, PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);
    }

    /**
     * Get a block texture as a GdImage
     * @param Block $block
     * @param BlockModelInterface|null $model
     * @param Chunk $chunk
     * @param TerrainTextures $terrainTextures
     * @return GdImage|null the texture of the block
     */
    private static function createBlockTexture(Block $block, ?BlockModelInterface $model, Chunk $chunk, TerrainTextures $terrainTextures): ?GdImage
    {
        if (BlockUtils::isInvisible($block)) return null;

        // get the biome
        $pos = $block->getPosition()->floor();
        $biomeId = $chunk->getBiomeId((int)$pos->getX(), (int)$pos->getY(), (int)$pos->getZ());
        $biome = BiomeRegistry::getInstance()->getBiome($biomeId);

        $differentModel = BlockUtils::hasDifferentModelForSameState($block);
        if (!$differentModel) {
            // block has no different models for the same state
            $img = self::getTextureFromCache($block, $biome);
            if ($img !== null) return $img;
        }

        // get the texture path
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

        // get the size of the image
        [$sx, $sy] = [imagesx($img), imagesy($img)];
        if ($sx > PocketMap::TEXTURE_SIZE || $sy > PocketMap::TEXTURE_SIZE) {
            $scaledImg = self::getEmptyTexture();
            // use sx for both the width and height, since a higher y value can be the result of an animated texture
            // like sea lanterns
            imagecopyresized(
                $scaledImg, $img,
                0, 0, 0, 0,
                PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE, $sx, $sx
            );
            // set the scaled image as the new image
            $img = $scaledImg;
        }

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

        // store in the cache
        if (!$differentModel) self::cacheTexture($block, $biome, $modelImg);

        return $modelImg;
    }

    /**
     * Get a texture already stored in the block cache
     * @param Block $block
     * @param Biome $biome
     * @return GdImage|null
     */
    private static function getTextureFromCache(Block $block, Biome $biome): ?GdImage
    {


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

        return null;
    }

    /**
     * Store a copy of the given block model in the cache
     * @param Block $block
     * @param Biome $biome
     * @param GdImage $modelImg
     * @return void
     */
    private static function cacheTexture(Block $block, Biome $biome, GdImage $modelImg): void
    {
        // create a cache image
        $cacheImg = self::getEmptyTexture();
        if ($cacheImg === false) return;

        imagealphablending($cacheImg, false);
        imagecopy($cacheImg, $modelImg, 0, 0, 0, 0, PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);
        imagesavealpha($cacheImg, true);

        // store the cache image
        self::$blockTextureMap[$biome->getId()][$block->getStateId()] = $cacheImg;
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
     * Get a compressed image
     * @param GdImage $src the image to compress
     * @param int $newWidth the new width of the image
     * @param int $newHeight the new height of the image
     * @return GdImage|null the compressed image with the new weight and height
     */
    public static function getCompressedImage(GdImage $src, int $newWidth, int $newHeight): ?GdImage
    {
        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);

        $compressedImg = imagecreatetruecolor($newWidth, $newHeight);
        if ($compressedImg === false) return null;
        imagealphablending($compressedImg, false);
        imagecopyresized($compressedImg, $src, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
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

        // replace some textures based upon the block
        if ($block instanceof Bed) {
            // this defaults to planks
            if ($block->isHeadPart()) return "bed_head_top";
            else return "bed_feet_top";
        } elseif ($block instanceof Campfire) {
            // this defaults to the campfire flame
            if ($block->isLit()) {
                // the soul and normal campfire have different textures
                if ($block instanceof SoulCampfire) return "soul_campfire_log_lit";
                return "campfire_log_lit";
            }
            return "campfire_log";
        } elseif ($block instanceof BigDripleafHead) {
            return "big_dripleaf_top";
        } elseif ($block instanceof BigDripleafStem) {
            return "big_dripleaf_stem";
        } elseif ($block instanceof SmallDripleaf) {
            if ($block->isTop()) return "small_dripleaf_top";
            else return "small_dripleaf_stem_top";
        }

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

    /**
     * @param GdImage $texture
     * @param int $rows number of rows to iterate over to determine the top colors
     * @return array<int>
     */
    public static function getTopColors(GdImage $texture, int $rows = PocketMap::TEXTURE_SIZE): array
    {
        $colors = [];
        for ($x = 0; $x < PocketMap::TEXTURE_SIZE; $x++) {
            // allocate transparent pixel
            $color = imagecolorallocatealpha($texture, 0, 0, 0, 127);
            if ($color === false) continue;

            for ($y = 0; $y < $rows; $y++) {
                $c = imagecolorat($texture, $x, $y);
                if ($c === false) continue;

                $index = imagecolorsforindex($texture, $c);
                if ($index["alpha"] < 127) {
                    $color = $c;
                    break;
                }
            }
            $colors[] = $color;
        }

        return $colors;
    }

}