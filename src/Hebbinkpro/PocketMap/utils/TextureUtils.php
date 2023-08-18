<?php

namespace Hebbinkpro\PocketMap\utils;

use GdImage;
use Hebbinkpro\PocketMap\utils\block\BlockDataValues;
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
     * @param ResourcePack $rp the resource pack
     * @param int $totalBlocks the amount of blocks visible in the texture
     * @param int $pixelsPerBlock the amount of pixels of each block
     * @return GdImage the texture image of the chunk
     */
    public static function createChunkTexture(Chunk $chunk, ResourcePack $rp, int $totalBlocks, int $pixelsPerBlock): GdImage
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
                $blockStateId = $chunk->getBlockStateId($bdx, $y, $bdz);
                $block = BlockStateParser::getBlockFromStateId($blockStateId);

                while (in_array($block->getTypeId(), $invalidBlocks) && $y > World::Y_MIN) {
                    $y--;
                    $blockStateId = $chunk->getBlockStateId($bdx, $y, $bdz);
                    $block = BlockStateParser::getBlockFromStateId($blockStateId);
                }

                $biomeId = $chunk->getBiomeId($bdx, $y, $bdz);
                $biome = BiomeRegistry::getInstance()->getBiome($biomeId);
                $blockTexture = self::createCompressedBlockTexture($block, $biome, $rp, $pixelsPerBlock);

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
     * @param ResourcePack $rp the resource pack
     * @param int $newSize the new size of the block texture
     * @return GdImage the block texture compressed to the $newSize
     */
    public static function createCompressedBlockTexture(Block $block, Biome $biome, ResourcePack $rp, int $newSize): GdImage
    {
        $img = self::createBlockTexture($block, $biome, $rp);

        $compressedImg = self::getCompressedImage($img, $rp->getTextureSize(), $rp->getTextureSize(), $newSize, $newSize);
        imagedestroy($img);

        return $compressedImg;
    }

    /**
     * Get a block texture as an GdImage
     * @param Block $block
     * @param Biome $biome
     * @param ResourcePack $rp
     * @return GdImage the texture of the block
     */
    public static function createBlockTexture(Block $block, Biome $biome, ResourcePack $rp): GdImage
    {
        if (!array_key_exists($rp->getPath(), self::$blockTextureMap)) {
            self::$blockTextureMap[$rp->getPath()] = [];
        }
        if (!array_key_exists($block->getTypeId(), self::$blockTextureMap[$rp->getPath()])) {
            self::$blockTextureMap[$rp->getPath()][$block->getTypeId()] = [];
        }

        // texture exists in the cache, return it
        if (array_key_exists($biome->getId(), self::$blockTextureMap[$rp->getPath()][$block->getTypeId()])) {
            $cacheImg = self::$blockTextureMap[$rp->getPath()][$block->getTypeId()][$biome->getId()];

            // create image and copy the cache data to it
            $img = imagecreatetruecolor($rp->getTextureSize(), $rp->getTextureSize());
            imagecopy($img, $cacheImg, 0, 0, 0, 0, $rp->getTextureSize(), $rp->getTextureSize());

            // return the copy of the cached image
            return $img;
        }

        // texture does not exist in the cache, create it

        // get the path of the texture
        $path = self::getBlockTexture($block, $rp);

        // block doesn't exist in the resource pack
        if ($path === null) {
            // set the path to the fallback texture
            $path = $rp->getFallbackTexturePath();
        }


        if (is_file($path . ".png")) $img = imagecreatefrompng($path . ".png");
        else if (is_file($path . ".tga")) $img = imagecreatefromtga($path . ".tga");
        else {
            // the path is null, return empty image
            $img = imagecreatetruecolor($rp->getTextureSize(), $rp->getTextureSize());
            imagecolorallocatealpha($img, 0, 0, 0, 127);
        }

        imagealphablending($img, true);
        self::applyColorMap($img, $block, $biome, $rp);

        // create a cache image
        $cacheImg = imagecreatetruecolor($rp->getTextureSize(), $rp->getTextureSize());
        imagecopy($cacheImg, $img, 0, 0, 0, 0, $rp->getTextureSize(), $rp->getTextureSize());

        // store the cache image
        self::$blockTextureMap[$rp->getPath()][$block->getTypeId()][$biome->getId()] = $cacheImg;
        return $img;
    }

    /**
     * Get the texture of a given block
     * @param Block|null $block the block to get the texture of
     * @param ResourcePack $rp the path to the resource pack
     * @return string|null the path to the texture or null when not found
     */
    public static function getBlockTexture(?Block $block, ResourcePack $rp): ?string
    {
        if ($block === null) return null;
        $name = self::getBlockTextureName($block);

        // get the block data
        $blockData = $rp->getBlocks()[$name];
        // block data does not exist
        if (!$blockData) return null;
        $blockTextures = $blockData["textures"];

        // get the terrain texture name of the block to use
        $textureName = is_string($blockTextures) ? $blockTextures : ($blockTextures["up"] ?? array_values($blockTextures)[0]);

        // get the terrain data
        $terrainTextures = $rp->getTerrainTextures()["texture_data"];

        // get the terrain data of the given texture
        // if the block does not exist in the terrain_texture.json file for some reason, give the expected texture
        $terrainData = $terrainTextures[$textureName] ?? ["textures" => "textures/blocks/$textureName"];
        $textures = $terrainData["textures"];

        // the texture is just a straight forward texture path
        if (is_string($textures)) return $rp->getPath() . $textures;

        // well done, you found a block without texture
        if (!is_array($textures) || empty($textures)) return null;

        // texture contains image path and tint_color
        if (is_array($textures[0])) {
            // it doesn't have a path for some reason
            if (!array_key_exists("path", $textures[0])) return null;
            // return the path
            return $rp->getPath() . $textures[0]["path"];
        }

        // only a single entry
        if (count($textures) == 1) return $rp->getPath() . $textures[0];

        // get the data value of the block
        // the data value determines which texture to use of a list of textures
        $dataValue = BlockDataValues::getDataValue($block);

        // unknown data value
        if (!$textures[$dataValue]) return null;

        // check if the "path"
        if (is_array($textures[$dataValue])) return $rp->getPath() . $textures[$dataValue]["path"];
        return $rp->getPath() . $textures[$dataValue];
    }

    /**
     * Apply a color map to the given block texture
     * @param GdImage $texture the texture to apply the color map on
     * @param Block $block the block of the texture
     * @param Biome $biome the biome the block is in
     * @param ResourcePack $rp the resource pack
     * @return void
     */
    public static function applyColorMap(GdImage $texture, Block $block, Biome $biome, ResourcePack $rp): void
    {
        // get the color from the cache
        $colorMap = ColorMapParser::getColorFromBlock($block, $biome, $rp);

        // the given block is not mapped
        if ($colorMap < 0) return;

        self::overlay($texture, $colorMap, $rp->getTextureSize());
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
        $rps = array_keys(self::$blockTextureMap);
        foreach ($rps as $rp) {
            unset(self::$blockTextureMap[$rp]);
        }
    }

    /**
     * Get the texture name of a block.
     * - In MOST cases, it's just the name with minecraft: removed, or _block_ replaced with _.
     * - There are some exceptions in which that's not the case and this is fixed with the match.
     * @param Block $block the name of the block
     * @return string the texture name
     */
    public static function getBlockTextureName(Block $block): string {
        $stateData = BlockStateParser::getBlockStateData($block);
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

        // remove minecraft: and _block_ from the name
        return str_replace(["minecraft:", "_block_"], ["", "_"], $name);
    }
}