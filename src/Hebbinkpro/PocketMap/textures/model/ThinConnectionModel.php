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

use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class ThinConnectionModel extends ConnectionModel
{

    public function getCenterGeometry(Block $block): array
    {
        return [
            [
                [7, 7],
                [2, 2]
            ]
        ];
    }

    public function getConnectionsGeometry(Block $block, Chunk $chunk): array
    {
        // TODO make it work with the textures
        // east = 90 degrees rotated north & west = 90 degrees rotated south
        // North = [[7,0],[2,7]], South = [[7,9],[2,7]]
        $geo = [];

        $connections = BlockUtils::getConnections($block, $chunk);

        foreach ($connections as $face) {
            $faceGeo = match ($face) {
                Facing::NORTH => [
                    [7, 0],
                    [2, 7]
                ],
                Facing::EAST => [
                    [9, 7],
                    [7, 2]
                ],
                Facing::SOUTH => [
                    [7, 9],
                    [2, 7]
                ],
                Facing::WEST => [
                    [0, 7],
                    [7, 2]
                ],
                default => null
            };

            if ($faceGeo !== null) $geo[] = $faceGeo;
        }

        return $geo;
    }
}