<?php

namespace Hebbinkpro\PocketMap\utils;

use GdImage;
use Hebbinkpro\PocketMap\utils\block\BlockDataValues;
use Hebbinkpro\PocketMap\utils\block\BlockStateParser;
use Hebbinkpro\PocketMap\utils\block\old\OldBlockTypeNames;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\world\biome\Biome;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

class TextureUtils
{
    private static array $blockTextureMap = [];

    public static function createTextureFromChunk(Chunk $chunk, ResourcePack $rp, int $pixelsPerBlock): GdImage
    {

        $textureSize = $pixelsPerBlock * 16;
        $texture = imagecreatetruecolor($textureSize, $textureSize);

        $invalidBlocks = [
            BlockTypeIds::FERN,
            BlockTypeIds::TALL_GRASS,
            BlockTypeIds::DOUBLE_TALLGRASS
        ];

        for ($bdx = 0; $bdx < 16; $bdx++) {
            for ($bdz = 0; $bdz < 16; $bdz++) {
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

                $tx = $bdx * $pixelsPerBlock;
                $ty = $bdz * $pixelsPerBlock;
                imagecopy($texture, $blockTexture, $tx, $ty, 0, 0, $pixelsPerBlock, $pixelsPerBlock);
            }
        }

        return $texture;
    }

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
     * @return GdImage
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

        $path = self::getBlockTexture($block, $rp);


        if (is_file($path . ".png")) $img = imagecreatefrompng($path . ".png");
        else if (is_file($path . ".tga")) $img = imagecreatefromtga($path . ".tga");
        else {
            // return empty image
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
     * @param Block $block the block to get the texture of
     * @param ResourcePack $rp the path to the resource pack
     * @return string|null the path to the texture
     */
    public static function getBlockTexture(Block $block, ResourcePack $rp): ?string
    {
        if ($block->getTypeId() == 0) return null;
        $stateData = BlockStateParser::getBlockStateData($block);
        // remove the 'minecraft:' prefix
        // also replace '_block_' with '_' if it exists, otherwise stone slabs will not be handled correctly
        // in the files it is stone_block_slab and in the texture files it is stone_slab

        // TODO remove the match when the resource pack supports it
        $name = str_replace(["minecraft:", "_block_"], ["", "_"], match ($stateData->getName()) {
            BlockTypeNames::OAK_LOG, BlockTypeNames::BIRCH_LOG, BlockTypeNames::SPRUCE_LOG, BlockTypeNames::JUNGLE_LOG
            => OldBlockTypeNames::LOG,
            BlockTypeNames::ACACIA_LOG, BlockTypeNames::DARK_OAK_LOG
            => OldBlockTypeNames::LOG2,
            default => $stateData->getName()
        });

        // get the block data
        $blockData = $rp->getBlocks()[$name];
        // block data does not exist
        if (!$blockData) return null;
        $blockTextures = $blockData["textures"];

        // get the terrain texture name of the block to use
        $textureName = is_string($blockTextures) ? $blockTextures : ($blockTextures["up"] ?? array_values($blockTextures)[0]);

        // get the terrain data
        $terrainTextures = $rp->getTerrainTextures()["texture_data"];
        $terrainData = $terrainTextures[$textureName];
        // terrain texture does not exist
        if (!$terrainData) return null;
        $textures = $terrainData["textures"];

        // the texture is just a straight forward texture path
        if (is_string($textures)) return $rp->getPath() . $textures;

        if (array_key_exists("path", $textures)) return $textures["path"] . $textures;

        // well done, you found a block without texture
        if (!is_array($textures) || empty($textures)) return null;

        // only 1 entry
        if (count($textures) == 1) return $rp->getPath() . $textures[0];

        // remove 1 to match the array index
        $dataValue = BlockDataValues::getDataValue($block);

        // unknown data value
        if (!$textures[$dataValue]) return null;

        // check if the "path"
        if (is_array($textures[$dataValue])) return $rp->getPath() . $textures[$dataValue]["path"];
        return $rp->getPath() . $textures[$dataValue];
    }

    public static function applyColorMap(GdImage $texture, Block $block, Biome $biome, ResourcePack $rp): void
    {
        // get the color from the cache
        $colorMap = ColorMapParser::getColorFromBlock($block, $biome, $rp);

        // the given block is not mapped
        if ($colorMap < 0) return;

        self::overlay($texture, $colorMap, 128, $rp->getTextureSize());
    }

    public static function overlay(GdImage $image, int $overlay, int $a, int $size): void
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

    public static function getCompressedImage(GdImage $src, int $srcWidth, int $srcHeight, int $newWidth, int $newHeight): GdImage
    {
        $compressedImg = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresized($compressedImg, $src, 0, 0, 0, 0, $newWidth, $newHeight, $srcHeight, $srcWidth);

        return $compressedImg;
    }

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
}