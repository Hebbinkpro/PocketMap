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

use pocketmine\block\Block;
use pocketmine\block\Torch;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class TorchModel extends HorizontalFacingModel
{

    public function getGeometry(Block $block, Chunk $chunk): array
    {
        if ($block instanceof Torch) {
            $facing = $block->getFacing();

            if (in_array($facing, Facing::HORIZONTAL)) {
                return [
                    // top
                    [
                        [7,6], // start
                        [2,2], // size
                        [7,11]  // dest
                    ],
                    // stick
                    [
                        [7,8],
                        [2,8],
                        [7,13],
                        [2,3]
                    ]
                ];
            }
        }

        // top in a block center
        return [
            [
                [7,6], // start
                [2,2], // size
                [7,7]  // dest
            ]
        ];
    }
}