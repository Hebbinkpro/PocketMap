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

use pocketmine\math\Facing;

class EndRodModel extends AnyFacingModel
{
    public function getSideFacing(): array
    {
        return [
            Facing::NORTH,
            Facing::EAST,
            Facing::SOUTH,
            Facing::WEST
        ];
    }

    public function getTopGeometry(int $facing): array
    {
        $geo = [
            // 4x4 bottom
            [
                [2, 3],
                [4, 4],
                [6, 6]
            ],
        ];

        if ($facing == Facing::UP) {
            // 2x2 top
            $geo[] = [
                [2, 0],
                [2, 2],
                [7, 7]
            ];
        }

        return $geo;
    }

    public function getSideGeometry(int $facing): array
    {
        return [
            // 4x1 bottom
            [
                [2, 2],
                [4, 1],
                [6, 15]
            ],
            // 2x15 rod
            [
                [0, 0],
                [2, 15],
                [7, 0]
            ]
        ];
    }

}