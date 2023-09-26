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
use Hebbinkpro\PocketMap\textures\TerrainTextures;
use Hebbinkpro\PocketMap\utils\block\BlockStateParser;
use Hebbinkpro\PocketMap\utils\block\OldBlockTypeNames as OBTN;
use pocketmine\block\Block;
use pocketmine\data\bedrock\block\BlockTypeNames as BTN;
use pocketmine\math\Facing;
use pocketmine\world\biome\Biome;

class TextureUtils
{
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
        return floor(max($totalPixels / $blocks, 1));
    }

    /**
     * Create a block texture compressed to the given size
     * @param Block $block the block
     * @param Biome $biome the biome the block is in
     * @param TerrainTextures $terrainTextures the resource pack
     * @param int $newSize the new size of the block texture
     * @return GdImage the block texture compressed to the $newSize
     */
    public static function createCompressedBlockTexture(Block $block, Biome $biome, TerrainTextures $terrainTextures, int $newSize): GdImage
    {
        $img = self::createBlockTexture($block, $biome, $terrainTextures);
        $compressedImg = self::getCompressedImage($img, PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE, $newSize, $newSize);

        imagedestroy($img);

        return $compressedImg;
    }

    /**
     * Get a block texture as an GdImage
     * @param Block $block
     * @param Biome $biome
     * @param TerrainTextures $terrainTextures
     * @return GdImage the texture of the block
     */
    public static function createBlockTexture(Block $block, Biome $biome, TerrainTextures $terrainTextures): GdImage
    {
        if (!array_key_exists($terrainTextures->getPath(), self::$blockTextureMap)) {
            self::$blockTextureMap[$terrainTextures->getPath()] = [];
        }
        if (!array_key_exists($block->getTypeId(), self::$blockTextureMap[$terrainTextures->getPath()])) {
            self::$blockTextureMap[$terrainTextures->getPath()][$block->getTypeId()] = [];
        }

        // texture exists in the cache, return it
        if (array_key_exists($biome->getId(), self::$blockTextureMap[$terrainTextures->getPath()][$block->getTypeId()])) {
            $cacheImg = self::$blockTextureMap[$terrainTextures->getPath()][$block->getTypeId()][$biome->getId()];

            // create image and copy the cache data to it
            $img = imagecreatetruecolor(PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);
            imagealphablending($img, false);
            imagecopy($img, $cacheImg, 0, 0, 0, 0, PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);
            imagesavealpha($img, true);

            // return the copy of the cached image
            return $img;
        }

        if (($path = $terrainTextures->getBlockTexturePath($block)) === null) {
            // set the path to the fallback texture
            $path = $terrainTextures->getRealTexturePath($terrainTextures->getOptions()->getFallbackBlock());
        }

        if (is_file($path . ".png")) $img = imagecreatefrompng($path . ".png");
        else if (is_file($path . ".tga")) $img = imagecreatefromtga($path . ".tga");
        else {
            // the path is null, return empty image
            $img = imagecreatetruecolor(PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);
            imagecolorallocatealpha($img, 0, 0, 0, 127);
        }
        imagealphablending($img, false);

        self::applyColorMap($img, $block, $biome, $terrainTextures);
        imagesavealpha($img, true);

        // create a cache image
        $cacheImg = imagecreatetruecolor(PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);
        imagealphablending($cacheImg, false);
        imagecopy($cacheImg, $img, 0, 0, 0, 0, PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);
        imagesavealpha($cacheImg, true);

        // store the cache image
        self::$blockTextureMap[$terrainTextures->getPath()][$block->getTypeId()][$biome->getId()] = $cacheImg;


        return $img;
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
                $c = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                if ($c["alpha"] == 127) continue; // transparent pixel

                $cr = [
                    "red" => floor($c["red"] * $rm),
                    "green" => floor($c["green"] * $gm),
                    "blue" => floor($c["blue"] * $bm),
                    "alpha" => $c["alpha"]
                ];

                imagesetpixel($image, $x, $y, imagecolorexactalpha($image, $cr["red"], $cr["green"], $cr["blue"], $cr["alpha"]));
            }
        }
    }

    /**
     * Get a compressed image
     * @param GdImage $src the image to compress
     * @param int $srcWidth the width of the image
     * @param int $srcHeight teh height of the image
     * @param int $newWidth the new width of the image
     * @param int $newHeight the new height of the image
     * @return GdImage the compressed image with the new weight and height
     */
    public static function getCompressedImage(GdImage $src, int $srcWidth, int $srcHeight, int $newWidth, int $newHeight): GdImage
    {
        $compressedImg = imagecreatetruecolor($newWidth, $newHeight);
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
                $c = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $c["alpha"] += $alpha;
                if ($c["alpha"] > 127) $c["alpha"] = 127;
                else if ($c["alpha"] < 0) $c["alpha"] = 0;

                imagesetpixel($image, $x, $y, imagecolorexactalpha($image, $c["red"], $c["green"], $c["blue"], $c["alpha"]));
            }
        }
    }

    /**
     * Rotate an image on the given axis
     * @param GdImage $image
     * @param int $facing
     * @return GdImage
     */
    public static function rotateToFacing(GdImage $image, int $facing): GdImage
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
        return imagerotate($image, $angle, 0);

    }

    /**
     * Get the texture name of a block.
     * - In MOST cases, it's just the name with minecraft: removed, or _block_ replaced with _.
     * - There are some exceptions in which that's not the case and this is fixed with the match.
     * @param Block $block the name of the block
     * @return string|null the texture name
     */
    public static function getBlockTextureName(Block $block): ?string
    {
        $stateData = BlockStateParser::getBlockStateData($block);
        if ($stateData === null) return null;

        $name = self::getBlockTypeName($stateData->getName());

        // replace _block_ with _
        return str_replace("_block_", "_", self::removeBlockTypeNamePrefix($name));
    }

    public static function getBlockTypeName(string $stateName): string
    {
        return match ($stateName) {
            BTN::OAK_LOG, BTN::BIRCH_LOG, BTN::SPRUCE_LOG, BTN::JUNGLE_LOG
            => OBTN::LOG,
            BTN::ACACIA_LOG, BTN::DARK_OAK_LOG
            => OBTN::LOG2,

            BTN::WHITE_CONCRETE, BTN::ORANGE_CONCRETE, BTN::MAGENTA_CONCRETE, BTN::LIGHT_BLUE_CONCRETE,
            BTN::YELLOW_CONCRETE, BTN::LIME_CONCRETE, BTN::PINK_CONCRETE, BTN::GRAY_CONCRETE,
            BTN::LIGHT_GRAY_CONCRETE, BTN::CYAN_CONCRETE, BTN::PURPLE_CONCRETE, BTN::BLUE_CONCRETE,
            BTN::BROWN_CONCRETE, BTN::GREEN_CONCRETE, BTN::RED_CONCRETE, BTN::BLACK_CONCRETE
            => OBTN::CONCRETE,

            BTN::WHITE_CONCRETE_POWDER, BTN::ORANGE_CONCRETE_POWDER, BTN::MAGENTA_CONCRETE_POWDER, BTN::LIGHT_BLUE_CONCRETE_POWDER,
            BTN::YELLOW_CONCRETE_POWDER, BTN::LIME_CONCRETE_POWDER, BTN::PINK_CONCRETE_POWDER, BTN::GRAY_CONCRETE_POWDER,
            BTN::LIGHT_GRAY_CONCRETE_POWDER, BTN::CYAN_CONCRETE_POWDER, BTN::PURPLE_CONCRETE_POWDER, BTN::BLUE_CONCRETE_POWDER,
            BTN::BROWN_CONCRETE_POWDER, BTN::GREEN_CONCRETE_POWDER, BTN::RED_CONCRETE_POWDER, BTN::BLACK_CONCRETE_POWDER
            => OBTN::CONCRETE_POWDER,

            // just return the name
            default => $stateName
        };
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
        // destroy all stored images
        foreach (self::$blockTextureMap as $blocks) {
            foreach ($blocks as $biomes) {
                foreach ($biomes as $img) {
                    imagedestroy($img);
                }
            }
        }

        // clear cache of all resource packs
        $textures = array_keys(self::$blockTextureMap);
        foreach ($textures as $path) {
            unset(self::$blockTextureMap[$path]);
        }
    }

    public static function getBlockFaceTexture(Block $block, array $availableFaces): ?string
    {
        if (empty($availableFaces)) return null;

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