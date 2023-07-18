<?php

namespace Hebbinkpro\PocketMap\task;

use GdImage;
use Hebbinkpro\PocketMap\render\Region;
use Hebbinkpro\PocketMap\render\RegionChunks;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\utils\ColorMapParser;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\scheduler\AsyncTask;

class AsyncRegionRenderTask extends AsyncTask
{
    /**
     * Create a new image and add all given chunks.
     */
    public const RENDER_MODE_FULL = 0;
    /**
     * Use an existing image and update only the given chunks.
     * When there does not already exist a render, the full render will be used.
     */
    public const RENDER_MODE_PARTIAL = 1;

    private string $regionChunks;
    private string $region;
    private int $renderMode;
    private string $renderFile;

    public function __construct(string $renderPath, RegionChunks $regionChunks, int $renderMode = self::RENDER_MODE_FULL)
    {
        // store encoded region
        $this->regionChunks = $regionChunks->encode();

        $region = $regionChunks->getRegion();
        $this->region = serialize($region);

        $this->renderMode = $renderMode;

        // get the name of the file
        $zoom = $region->getZoom();
        $rx = $region->getX();
        $rz = $region->getZ();
        $this->renderFile = $renderPath . "$zoom/$rx,$rz.png";
    }


    /**
     * Run the correct render mode behaviour.
     * - RENDER_MODE_PARTIAL: renderPartial
     * - RENDER_MODE_FULL: renderFull
     */
    public function onRun(): void
    {
        // decode the region
        /** @var Region $region */
        $region = unserialize($this->region);

        // partial render
        if ($this->renderMode == self::RENDER_MODE_PARTIAL) {
            $this->renderPartial($region);
            return;
        }

        // full render
        $this->renderFull($region);
    }

    /**
     * Use an existing image to render the given chunks on.
     * If there doesn't exist an image, full render is used.
     * @param Region $region
     * @return void
     */
    private function renderPartial(Region $region): void
    {
        // the given region does not yet exist
        if (!file_exists($this->renderFile)) {
            // make a full render of the region
            $this->renderFull($region);
            return;
        }

        // get the image from the png and draw the chunks
        $regionImg = imagecreatefrompng($this->renderFile);
        $this->drawChunks($region, $regionImg);

        // store the image
        $this->storeRegionImage($regionImg);
    }

    /**
     * Create a full render of the region
     * @param Region $region
     * @return void
     */
    private function renderFull(Region $region): void
    {
        // create base image
        $regionImg = imagecreatetruecolor(WorldRenderer::RENDER_SIZE, WorldRenderer::RENDER_SIZE);

        // draw the chunks on the image
        $this->drawChunks($region, $regionImg);

        // store the image
        $this->storeRegionImage($regionImg);
    }

    /**
     * Draw the chunks of the given region on the given image
     * @param Region $region the region
     * @param GdImage $image the image to draw the chunks on
     * @return void
     */
    private function drawChunks(Region $region, GdImage $image): void
    {
        $rp = $region->getResourcePack();

        $chunkSize = $region->getChunkPixelSize();

        // amount of visible full blocks inside the render
        $totalBlocks = TextureUtils::getTotalBlocks($chunkSize);
        // the size of pixels of each visible block
        $imgPixelsPerBlock = TextureUtils::getPixelsPerBlock($chunkSize, $totalBlocks);
        // the total size of the chunk image
        $imgChunkSize = $totalBlocks * $imgPixelsPerBlock;

        $chunks = [];
        // yield all chunks
        foreach (RegionChunks::yieldAllEncodedChunks($this->regionChunks) as [$cx, $cz, $chunk]) {
            // save the chunk to the region data
            $chunks[] = [$cx, $cz];

            // create the chunk image
            $chunkImg = TextureUtils::createChunkTexture($chunk, $rp, $totalBlocks, $imgPixelsPerBlock);

            [$rcx, $rcz] = $region->getRegionChunkCoords($cx, $cz);

            // pixel coords the chunk starts
            $dx = floor($rcx * $chunkSize);
            $dz = floor($rcz * $chunkSize);

            // copy the chunk img onto the render
            // if imgPixelsPerBlock>pixelsPerBlock, this will resize it to the smaller variant
            imagecopyresized($image, $chunkImg, $dx, $dz, 0, 0, $chunkSize, $chunkSize, $imgChunkSize, $imgChunkSize);
            imagedestroy($chunkImg);
        }

        $this->setResult(serialize($chunks));

        // clear the image cache
        $this->clearCache();
    }

    /**
     * Clear the cache of the ColorMap parser and TextureUtils to make some memory free
     * @return void
     */
    public function clearCache(): void
    {
        ColorMapParser::clearCache();
        TextureUtils::clearCache();
    }

    /**
     * Store a given image on the renderFile path
     * @param GdImage $image the image to store
     * @return void
     */
    private function storeRegionImage(GdImage $image): void
    {
        // create a png from the image and save it to the file
        imagepng($image, $this->renderFile);
        // destroy the image from the memory
        imagedestroy($image);
    }

    public function onCompletion(): void
    {
        /** @var Region $region */
        $region = unserialize($this->region);

        // save the updated render data
        $region->addChunksToRenderData(unserialize($this->getResult()));

        // mark the render as finished
        RenderSchedulerTask::finishedRender($region);
    }
}