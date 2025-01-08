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
 * Copyright (c) 2024-2025 Hebbinkpro
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

abstract class BlockModel implements BlockModelInterface
{

    /**
     * Get the block model texture from the block texture
     * @param Block $block
     * @param Chunk $chunk
     * @param GdImage $texture
     * @return GdImage|null
     */
    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): ?GdImage
    {

        $modelTexture = TextureUtils::getEmptyTexture();
        if ($modelTexture === false) return null;

        $geo = $this->getGeometry($block, $chunk);
        foreach ($geo as $parts) {
            $srcStart = $parts[0];                  // [x,y] of the part on the texture
            $srcSize = $parts[1];                   // [width, height] of the part on the texture

            if (isset($parts[2]) && is_int($parts[2])) {
                // rotation is the third argument
                $dstStart = $srcStart;     // [x,y] of the part in the model
                $dstSize = $srcSize;    // [width, height] of the part in the model
                $rotation = $parts[2];             // rotation angle in degrees
            } else {
                // default order
                $dstStart = $parts[2] ?? $srcStart;     // [x,y] of the part in the model
                $dstSize = $parts[3] ?? $srcSize;    // [width, height] of the part in the model
                $rotation = $parts[4] ?? 0;             // rotation angle in degrees
            }

            if ($rotation != 0) {
                // time to rotate

                // convert clockwise to anti-clockwise rotation and make sure it is between 0 and 360
                $rotation = (360 - $rotation) % 360;

                // create an empty texture on which the resized part is copied
                $tmpTexture = TextureUtils::getEmptyTexture();
                imagealphablending($tmpTexture, true);
                imagecopyresized($tmpTexture, $texture, $dstStart[0], $dstStart[1], $srcStart[0], $srcStart[1], $dstSize[0], $dstSize[1], $srcSize[0], $srcSize[1]);
                imagesavealpha($tmpTexture, true);

                // rotate teh texture
                $tmpTexture = imagerotate($tmpTexture, $rotation, 0);

                # copy the rotated texture onto the model
                imagealphablending($tmpTexture, true);
                imagecopy($modelTexture, $tmpTexture, 0, 0, 0, 0, 16, 16);
                imagesavealpha($tmpTexture, true);
            } else {
                // copy the resized texture onto the model
                imagealphablending($texture, true);
                imagecopyresized($modelTexture, $texture, $dstStart[0], $dstStart[1], $srcStart[0], $srcStart[1], $dstSize[0], $dstSize[1], $srcSize[0], $srcSize[1]);
                imagesavealpha($texture, true);
            }

        }

        return $modelTexture;
    }

    /**
     * Get the block geometry.
     *
     *  A geometry is an array of parts, and a part is one of the following:
     *   - [start,size]
     *   - [start,size,destStart]
     *   - [start,size,destStart,destSize]
     *   - [start,size,destStart,destSize,rotation]
     *  If dest values are not given, the source values will be used
     * @return int[][][]
     */
    public abstract function getGeometry(Block $block, Chunk $chunk): array;

    /**
     * Default texture model of the full 16x16 texture
     * @return array[]
     */
    public static function getDefaultGeometry(): array
    {
        return [
            [
                [0, 0],
                [PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE]
            ]
        ];
    }
}