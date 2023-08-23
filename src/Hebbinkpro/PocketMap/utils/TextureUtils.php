<?php

namespace Hebbinkpro\PocketMap\utils;

use GdImage;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\terrainTextures\TerrainTextures;
use Hebbinkpro\PocketMap\utils\block\BlockStateParser;
use Hebbinkpro\PocketMap\utils\block\old\OldBlockTypeNames as OBTN;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\data\bedrock\block\BlockTypeNames as BTN;
use pocketmine\world\biome\Biome;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

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
     * Create the texture of the given chunk
     * @param Chunk $chunk the chunk
     * @param TerrainTextures $terrainTextures the resource pack
     * @param int $totalBlocks the amount of blocks visible in the texture
     * @param int $pixelsPerBlock the amount of pixels of each block
     * @return GdImage the texture image of the chunk
     */
    public static function createChunkTexture(Chunk $chunk, TerrainTextures $terrainTextures, int $totalBlocks, int $pixelsPerBlock): GdImage
    {
        $invalidBlocks = [
            BlockTypeIds::FERN,
            BlockTypeIds::TALL_GRASS,
            BlockTypeIds::DOUBLE_TALLGRASS
        ];

        //$totalBlocks = self::getTotalBlocks($maxTextureSize);
        //$pixelsPerBlock = self::getPixelsPerBlock($maxTextureSize, $totalBlocks);
        $textureSize = $totalBlocks * $pixelsPerBlock;

        $texture = imagecreatetruecolor($textureSize, $textureSize);

        // amount of blocks between two blocks to render
        // this is to prevent rendering of only the upper left corner for rendering when <16 pixels are available for a chunk
        $diff = floor(16 / $totalBlocks);

        // loop through all block indices that can be rendered
        for ($bdxI = 0; $bdxI < $totalBlocks; $bdxI++) {
            for ($bdzI = 0; $bdzI < $totalBlocks; $bdzI++) {
                // get the real x and z positions from the indices
                $bdx = $bdxI * $diff;
                $bdz = $bdzI * $diff;

                $y = $chunk->getHighestBlockAt($bdx, $bdz);

                // there is no block on this position
                if ($y === null) continue;

                $blockStateId = $chunk->getBlockStateId($bdx, $y, $bdz);
                $block = BlockStateParser::getBlockFromStateId($blockStateId);

                while (in_array($block->getTypeId(), $invalidBlocks) && $y > World::Y_MIN) {
                    $y--;
                    $blockStateId = $chunk->getBlockStateId($bdx, $y, $bdz);
                    $block = BlockStateParser::getBlockFromStateId($blockStateId);
                }

                $biomeId = $chunk->getBiomeId($bdx, $y, $bdz);
                $biome = BiomeRegistry::getInstance()->getBiome($biomeId);
                $blockTexture = self::createCompressedBlockTexture($block, $biome, $terrainTextures, $pixelsPerBlock);

                if ($y % 2 != 0 && ($alpha = $terrainTextures->getOptions()->getHeightOverlayAlpha()) > 0) {
                    $color = $terrainTextures->getOptions()->getHeightOverlayColor();
                    $r = ($color >> 16) & 0xff;
                    $g = ($color >> 8) & 0xff;
                    $b = $color & 0xff;

                    $heightOverlay = imagecreatetruecolor($pixelsPerBlock, $pixelsPerBlock);
                    imagefill($heightOverlay, 0, 0, imagecolorallocatealpha($heightOverlay, $r, $g, $b, 127 - $alpha));
                    imagecopy($blockTexture, $heightOverlay, 0, 0, 0, 0, $pixelsPerBlock, $pixelsPerBlock);
                }

                $tx = $bdxI * $pixelsPerBlock;
                $ty = $bdzI * $pixelsPerBlock;

                imagecopy($texture, $blockTexture, $tx, $ty, 0, 0, $pixelsPerBlock, $pixelsPerBlock);
            }
        }

        return $texture;
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
            imagecopy($img, $cacheImg, 0, 0, 0, 0, PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);

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

        imagealphablending($img, true);
        self::applyColorMap($img, $block, $biome, $terrainTextures);

        // create a cache image
        $cacheImg = imagecreatetruecolor(PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);
        imagecopy($cacheImg, $img, 0, 0, 0, 0, PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);

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

        self::overlay($texture, $colorMap, PocketMap::TEXTURE_SIZE);
    }

    /**
     * Apply an overlay to the image
     * @param GdImage $image the image
     * @param int $overlay the color of the overlay
     * @param int $size the size (in pixels) of the image
     * @return void
     */
    public static function overlay(GdImage $image, int $overlay, int $size): void
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
        imagecopyresized($compressedImg, $src, 0, 0, 0, 0, $newWidth, $newHeight, $srcHeight, $srcWidth);

        return $compressedImg;
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

        $name = match ($stateData->getName()) {
            // replace all (old) logs with log or log2, all new logs have their own name
            BTN::OAK_LOG, BTN::BIRCH_LOG, BTN::SPRUCE_LOG, BTN::JUNGLE_LOG
            => OBTN::LOG,
            BTN::ACACIA_LOG, BTN::DARK_OAK_LOG
            => OBTN::LOG2,

            // replace all concrete types with minecraft:concrete,
            // because concrete is the only block that doesn't have entries for single colors, but still uses the color indexes
            BTN::WHITE_CONCRETE, BTN::ORANGE_CONCRETE, BTN::MAGENTA_CONCRETE, BTN::LIGHT_BLUE_CONCRETE,
            BTN::YELLOW_CONCRETE, BTN::LIME_CONCRETE, BTN::PINK_CONCRETE, BTN::GRAY_CONCRETE,
            BTN::LIGHT_GRAY_CONCRETE, BTN::CYAN_CONCRETE, BTN::PURPLE_CONCRETE, BTN::BLUE_CONCRETE,
            BTN::BROWN_CONCRETE, BTN::GREEN_CONCRETE, BTN::RED_CONCRETE, BTN::BLACK_CONCRETE
            => OBTN::CONCRETE,

            // replace concrete_powder with concretePowder, but WHY minecraft it isn't that hard
            BTN::CONCRETE_POWDER
            => OBTN::CONCRETE_POWDER,

            // just return the name
            default => $stateData->getName()
        };

        // remove prefix from the item if it exists
        if (str_contains($name, ":")) $name = explode(":", $name)[1];
        // replace _block_ with _
        return str_replace("_block_", "_", $name);
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
}