<?php

namespace Hebbinkpro\PocketMap\render;

use GdImage;
use Hebbinkpro\PocketMap\region\Region;
use Hebbinkpro\PocketMap\region\RegionChunks;
use Hebbinkpro\PocketMap\textures\TerrainTextures;
use Hebbinkpro\PocketMap\utils\block\BlockStateParser;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\BlockTypeIds;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

class AsyncChunkRenderTask extends AsyncRenderTask
{
    private string $encodedChunks;

    public function __construct(RegionChunks $regionChunks, string $renderPath)
    {
        parent::__construct($regionChunks->getRegion(), $renderPath);

        $this->encodedChunks = $regionChunks->encode();
    }

    function render(Region $region, GdImage $image): string
    {
        $terrainTextures = $region->getTerrainTextures();

        $chunkSize = $region->getChunkPixelSize();

        // amount of visible full blocks inside the render
        $totalBlocks = TextureUtils::getTotalBlocks($chunkSize);
        // the size of pixels of each visible block
        $imgPixelsPerBlock = TextureUtils::getPixelsPerBlock($chunkSize, $totalBlocks);
        // the total size of the chunk image
        $imgChunkSize = $totalBlocks * $imgPixelsPerBlock;

        $chunks = [];

        $regionChunksGenerator = RegionChunks::yieldAllEncodedChunks($this->encodedChunks);
        unset($this->encodedChunks); // free up some space

        // yield all chunks
        foreach ($regionChunksGenerator as [$cx, $cz, $chunk]) {
            // save the chunk to the region data
            $chunks[] = [$cx, $cz];

            // create the chunk image
            $chunkImg = $this->createChunkTexture($chunk, $terrainTextures, $totalBlocks, $imgPixelsPerBlock);

            [$rcx, $rcz] = $region->getRegionChunkCoords($cx, $cz);

            // pixel coords the chunk starts
            $dx = floor($rcx * $chunkSize);
            $dz = floor($rcz * $chunkSize);

            // copy the chunk img onto the render
            // if imgPixelsPerBlock>pixelsPerBlock, this will resize it to the smaller variant
            imagecopyresized($image, $chunkImg, $dx, $dz, 0, 0, $chunkSize, $chunkSize, $imgChunkSize, $imgChunkSize);
            imagedestroy($chunkImg);
        }

        // clear the image cache
        $this->clearCache();

        return serialize($chunks);
    }

    /**
     * Create the texture of the given chunk
     * @param Chunk $chunk the chunk
     * @param TerrainTextures $terrainTextures the resource pack
     * @param int $totalBlocks the amount of blocks visible in the texture
     * @param int $pixelsPerBlock the amount of pixels of each block
     * @return GdImage the texture image of the chunk
     */
    private function createChunkTexture(Chunk $chunk, TerrainTextures $terrainTextures, int $totalBlocks, int $pixelsPerBlock): GdImage
    {
        $invalidBlocks = [
            BlockTypeIds::FERN,
            BlockTypeIds::TALL_GRASS,
            BlockTypeIds::DOUBLE_TALLGRASS
        ];

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

                $blockTexture = TextureUtils::createCompressedBlockTexture($block, $biome, $terrainTextures, $pixelsPerBlock);
                $blockTexture = TextureUtils::rotateToFacing($blockTexture, BlockStateParser::getBlockFace($block));

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
}