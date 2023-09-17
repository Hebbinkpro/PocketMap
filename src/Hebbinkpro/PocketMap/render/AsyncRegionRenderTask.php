<?php

namespace Hebbinkpro\PocketMap\render;

use Exception;
use GdImage;
use Hebbinkpro\PocketMap\region\Region;

class AsyncRegionRenderTask extends AsyncRenderTask
{

    protected function render(Region $region, GdImage $image): mixed
    {
        // it's another zoom, so we can use existing images from the lower zoom level
        $pZoom = $region->getZoom() + 1;
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
        } catch (Exception $e) {
            return null;
        }
    }
}