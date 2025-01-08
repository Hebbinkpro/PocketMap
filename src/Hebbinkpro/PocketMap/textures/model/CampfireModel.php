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

use Hebbinkpro\PocketMap\textures\model\geometry\ModelGeometry;
use Hebbinkpro\PocketMap\textures\model\geometry\TexturePosition;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

class CampfireModel extends HorizontalFacingModel
{
    public function getGeometry(Block $block, Chunk $chunk): ?array
    {

        $base = new ModelGeometry(
            srcStart: new TexturePosition(0, 8),
            srcSize: new TexturePosition(16, 6),
            dstStart: new TexturePosition(0, 5),
            rotation: 90
        );

        $log = new ModelGeometry(
            srcStart: TexturePosition::zero(),
            srcSize: new TexturePosition(16, 4),
            dstStart: new TexturePosition(0, 1),
        );

        return [
            $base,
            // logs on bottom
            $log->set(rotation: 90), // east log
            $log->set(rotation: 270), // west log
            // logs on top
            $log->set(rotation: 0), // north log
            $log->set(rotation: 180) // south log
        ];
    }
}