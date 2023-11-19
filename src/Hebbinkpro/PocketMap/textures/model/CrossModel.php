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

namespace Hebbinkpro\PocketMap\textures\model;

use GdImage;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

class CrossModel extends BlockModel
{

    /**
     * @inheritDoc
     */
    public function getGeometry(Block $block, Chunk $chunk): array
    {
        return [];
    }

    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): ?GdImage
    {
        $modelTexture = TextureUtils::getEmptyTexture();
        if ($modelTexture === false) return null;

        $colors = $this->getTopColors($texture);
        $size = PocketMap::TEXTURE_SIZE - 1;

        // skip the corners, otherwise these textures are dominant in larger zoom levels
        // and can cause ugly dark spots in e.g. a grass field
        for ($i = 1; $i < count($colors) - 1; $i++) {
            $color = $colors[$i];

            imagealphablending($modelTexture, false);
            imagesetpixel($modelTexture, $i, $i, $color);
            imagesetpixel($modelTexture, $i, $size - $i, $color);
            imagesavealpha($modelTexture, true);

        }

        imagedestroy($texture);
        return $modelTexture;
    }

    /**
     * @param GdImage $texture
     * @return array<int>
     */
    public function getTopColors(GdImage $texture): array
    {
        $colors = [];
        for ($x = 0; $x < PocketMap::TEXTURE_SIZE; $x++) {
            $color = imagecolorallocatealpha($texture, 0, 0, 0, 127);
            if ($color === false) continue;

            for ($y = 0; $y < PocketMap::TEXTURE_SIZE; $y++) {
                $c = imagecolorat($texture, $x, $y);
                if ($c === false) continue;

                $index = imagecolorsforindex($texture, $c);
                if (isset($index["alpha"]) && $index["alpha"] < 127) {
                    $color = $c;
                    break;
                }
            }
            $colors[] = $color;
        }

        return $colors;
    }
}