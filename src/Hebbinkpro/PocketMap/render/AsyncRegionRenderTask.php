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

namespace Hebbinkpro\PocketMap\render;

use Exception;
use GdImage;
use Hebbinkpro\PocketMap\region\Region;

class AsyncRegionRenderTask extends AsyncRenderTask
{

    protected function render(Region $region, GdImage $image): mixed
    {
        // it's another zoom, so we can use existing images from the lower zoom level
        $pZoom = $region->getZoom() - 1;
        $px = $region->getX() * 2;
        $pz = $region->getZ() * 2;
        $size = WorldRenderer::RENDER_SIZE / 2;

        for ($i = 0; $i <= 1; $i++) {
            for ($j = 0; $j <= 1; $j++) {
                $rx = $px + $i;
                $rz = $pz + $j;

                $renderImg = $this->getSmallerRender($pZoom, $rx, $rz);
                if ($renderImg === null) continue;

                $dx = $i * $size;
                $dz = $j * $size;

                imagecopyresized($image, $renderImg, $dx, $dz, 0, 0, $size, $size, WorldRenderer::RENDER_SIZE, WorldRenderer::RENDER_SIZE);
                imagedestroy($renderImg);
            }
        }

        return null;
    }

    private function getSmallerRender(int $zoom, int $x, int $z): ?GdImage
    {

        $path = $this->getRenderPath() . "$zoom/$x,$z.png";

        if (!is_file($path)) return null;

        try {
            return imagecreatefrompng($path);
        } catch (Exception) {
            return null;
        }
    }
}