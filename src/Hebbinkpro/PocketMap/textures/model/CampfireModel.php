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

use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

class CampfireModel extends HorizontalFacingModel
{
    public function getGeometry(Block $block, Chunk $chunk): array
    {

        $base = [
            [0, 8],
            [16, 6],
            [0, 5],
            [16, 6],
            90
        ];

        $log = [
            [0, 0],
            [16, 4],
            [0, 1],
            [16, 4]
        ];


        //        var_dump($campfire);

        return [
            $base,
            // logs on bottom
            [...$log, 90],      // east log
            [...$log, 270],    // west log
            // logs on top
            [...$log, 0],       // north log
            [...$log, 180]      // south log
        ];
    }
}