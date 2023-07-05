<?php

namespace Hebbinkpro\PocketMap\task;

use Hebbinkpro\PocketMap\render\Region;
use Hebbinkpro\PocketMap\render\RegionChunks;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\scheduler\AsyncTask;

class AsyncRegionRenderTask extends AsyncTask
{
    private string $renderPath;
    private string $regionChunks;
    private string $region;

    public function __construct(string $renderPath, RegionChunks $regionChunks)
    {
        $this->renderPath = $renderPath;
        // store encoded region
        $this->regionChunks = $regionChunks->encode();
        $this->region = serialize($regionChunks->getRegion());
    }


    public function onRun(): void
    {
        // decode the region
        /** @var Region $region */
        $region = unserialize($this->region);
        $zoom = $region->getZoom();
        $rx = $region->getRegionX();
        $rz = $region->getRegionZ();
        $rp = $region->getResourcePack();

        // create base image
        $regionImg = imagecreatetruecolor(WorldRenderer::RENDER_SIZE, WorldRenderer::RENDER_SIZE);
        $chunkSize = $region->getChunkPixelSize();

        // size of img in pixels, it's hard to generate pixels smaller then 1
        $imgPixelsPerBlock = max($region->getPixelsPerBlock(), 1);
        // size of a chunk with the imgPixelSize
        $imgChunkSize = 16 * $imgPixelsPerBlock;

        // yield all chunks
        foreach (RegionChunks::yieldAllEncodedChunks($this->regionChunks) as [$cx, $cz, $chunk]) {
            // create the chunk image
            $chunkImg = TextureUtils::createTextureFromChunk($chunk, $rp, $imgPixelsPerBlock);

            [$rcx, $rcz] = $region->getRegionChunkCoords($cx, $cz);

            // pixel coords the chunk starts
            $dx = floor($rcx * $chunkSize);
            $dz = floor($rcz * $chunkSize);

            // copy the chunk img onto the render
            // if imgPixelsPerBlock>pixelsPerBlock, this will resize it to the smaller variant
            imagecopyresized($regionImg, $chunkImg, $dx, $dz, 0, 0, $chunkSize, $chunkSize, $imgChunkSize, $imgChunkSize);
            imagedestroy($chunkImg);
        }

        // store the image
        $name = $this->renderPath . "$zoom/$rx,$rz.png";
        imagepng($regionImg, $name);
        imagedestroy($regionImg);
    }
}