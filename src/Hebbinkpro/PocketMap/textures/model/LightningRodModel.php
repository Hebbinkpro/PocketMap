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

namespace Hebbinkpro\PocketMap\textures\model;

use pocketmine\block\Block;
use pocketmine\block\LightningRod;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class LightningRodModel extends AnyFacingModel
{
    public function getGeometry(Block $block, Chunk $chunk): array
    {
        if (!$block instanceof LightningRod) return parent::getGeometry($block, $chunk);

        // 4x4 top
        $top = [
            [0, 0],
            [4, 4],
            [6, 6]
        ];
        return match ($block->getFacing()) {
            Facing::DOWN => [
                $top,
                // 2x2 bottom
                [
                    [0, 0],
                    [2, 2],
                    [7, 7]
                ]
            ],
            Facing::UP => [
                $top
            ],
            default => [
                // 4x4 top
                [
                    [0, 0],
                    [4, 4],
                    [6, 0]
                ],
                // 2x15 rod
                [
                    [0, 4],
                    [2, 12],
                    [7, 4]
                ]
            ]
        };
    }
}