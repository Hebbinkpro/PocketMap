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
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

abstract class RotatedBlockModel extends BlockModel
{

    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): ?GdImage
    {
        // construct the original texture
        $texture = parent::getModelTexture($block, $chunk, $texture);
        if ($texture === null) return null;

        // convert clockwise to anti-clockwise rotation and make sure it is between 0 and 360
        $rotation = (360 - $this->getRotation($block)) % 360;

        if ($rotation != 0) {
            // rotate the texture
            $texture = imagerotate($texture, $rotation, 0);
        }

        return $texture;
    }

    /**
     * Get the clockwise rotation of the block model
     * @param Block $block
     * @return int
     */
    public abstract function getRotation(Block $block): int;

    /**
     * @inheritDoc
     */
    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        return self::getDefaultGeometry();
    }
}