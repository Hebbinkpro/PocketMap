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
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

abstract class BlockModel {

    /**
     * Get the block geometry
     * @return int[][][][]
     */
    public abstract function getGeometry(Block $block, Chunk $chunk): array;

    /**
     * Get the block model texture from the block texture
     * @param Block $block
     * @param Chunk $chunk
     * @param GdImage $texture
     * @return GdImage
     */
    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): GdImage {

        $modelTexture = TextureUtils::getEmptyTexture();

        $geo = $this->getGeometry($block, $chunk);
        foreach ($geo as $parts) {
            $srcStart = $parts[0];
            $width = $parts[1];
            $dstStart = $parts[2] ?? $srcStart;
            imagealphablending($texture, true);
            imagecopy($modelTexture, $texture, $dstStart[0], $dstStart[1], $srcStart[0], $srcStart[1], $width[0], $width[1]);
            imagesavealpha($texture, true);

        }

        imagedestroy($texture);
        return $modelTexture;
    }
}