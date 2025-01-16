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
 * Copyright (c) 2025 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\textures\model;

use Hebbinkpro\PocketMap\textures\model\geometry\ModelGeometry;
use Hebbinkpro\PocketMap\textures\model\geometry\TexturePosition;
use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class RedstoneModel extends BlockModel
{

    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        $connections = BlockUtils::getConnections($block, $chunk);

        $n = sizeof($connections);
        if ($n <= 1 || $n >= 4) {
            // connected to all sides
            return [self::getDefaultGeometry()];
        }

        // TODO in the current system it is impossible to get the line texture of redstone,
        //      since this is bound to surrounding blocks and is not stored in the block itself.
        //      To make it worse, the texture is grouped under "up" and "down" sides (worse then sweet berries...)
//        if ($n == 1 || $n == 2 && Facing::opposite($connections[0]) == $connections[1]) {
//            // 1 face or 2 faces and opposites, so it's a line (different texture)
//
//            // get the rotation for the line (default line is east-west rotated)
//            $rotation = 0;
//            if (in_array(Facing::NORTH, $connections)) $rotation = 90;
//
//            return [
//                new ModelGeometry(rotation: $rotation)
//            ];
//        }

        $geo = [
            ModelGeometry::fromCenter(8) // center dot
        ];

        foreach ($connections as $connection) {
            switch ($connection) {
                case Facing::NORTH:
                    $geo[] = new ModelGeometry(
                        srcstart: new TexturePosition(7, 0),
                        srcSize: TexturePosition::xy(4)
                    );
                    break;
                case Facing::SOUTH:
                    $geo[] = new ModelGeometry(
                        srcstart: new TexturePosition(6, 12),
                        srcSize: TexturePosition::xy(4)
                    );
                    break;
                case Facing::EAST:
                    $geo[] = new ModelGeometry(
                        srcstart: new TexturePosition(0, 6),
                        srcSize: TexturePosition::xy(4)
                    );
                    break;
                case Facing::WEST:
                    $geo[] = new ModelGeometry(
                        srcstart: new TexturePosition(12, 6),
                        srcSize: TexturePosition::xy(4)
                    );
                    break;

            }
        }

        return $geo;
    }
}