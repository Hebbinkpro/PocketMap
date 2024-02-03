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
 * Copyright (c) 2024 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\render;

use Exception;
use GdImage;
use Hebbinkpro\PocketMap\region\Region;
use Hebbinkpro\PocketMap\scheduler\RenderSchedulerTask;
use Hebbinkpro\PocketMap\utils\ColorMapParser;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\scheduler\AsyncTask;

abstract class AsyncRenderTask extends AsyncTask
{
    private string $renderPath;
    private string $serializedRegion;
    private string $renderFile;

    public function __construct(Region $region, string $renderPath)
    {
        $this->serializedRegion = serialize($region);
        $this->renderPath = $renderPath;
        $this->renderFile = $renderPath . $region->getName(false) . ".png";
    }

    public function onRun(): void
    {
        /** @var Region $region */
        $region = unserialize($this->serializedRegion);
        $renderImage = $this->getRenderImage();
        if ($renderImage === false) return;

        $result = $this->render($region, $renderImage);

        $this->storeRender($renderImage);
        $this->setResult($result);
    }

    /**
     * Get the render image or create a new image.
     * @return GdImage|false
     */
    private function getRenderImage(): GdImage|false
    {
        // the given region does not yet exist
        if (!is_file($this->renderFile)) {
            return imagecreatetruecolor(WorldRenderer::RENDER_SIZE, WorldRenderer::RENDER_SIZE);

        }

        return imagecreatefrompng($this->renderFile);
    }

    /**
     * Render the image
     * @param Region $region the region
     * @param GdImage $image the image the region has to be rendered on
     * @return mixed the result
     */
    abstract protected function render(Region $region, GdImage $image): mixed;

    /**
     * Store a given image on the renderFile path
     * @param GdImage $render the render image to store
     * @return void
     */
    protected function storeRender(GdImage $render): void
    {
        // create a png from the image and save it to the file
        imagepng($render, $this->renderFile);
        // destroy the image from the memory
        imagedestroy($render);
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
     * @throws Exception
     */
    public function onCompletion(): void
    {
        /** @var Region $region */
        $region = unserialize($this->serializedRegion);

        // mark the render as finished
        RenderSchedulerTask::finishRender($region);
    }

    /**
     * @return string
     */
    public function getRenderPath(): string
    {
        return $this->renderPath;
    }
}