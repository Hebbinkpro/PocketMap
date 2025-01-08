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
use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use pocketmine\block\Block;
use pocketmine\block\Wall;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class WallModel extends ConnectionModel
{
    public function getCenterGeometry(Block $block): array
    {
        if (!$block instanceof Wall || $block->isPost()) {
            return [ModelGeometry::fromCenter(8)];
        }

        return [ModelGeometry::fromCenter(6)];
    }

    public function getConnectionsGeometry(Block $block, Chunk $chunk): array
    {
        $geo = [];
        $length = $block instanceof Wall && $block->isPost() ? 4 : 5;

        $connections = BlockUtils::getConnections($block, $chunk);
        foreach ($connections as $face) {
            $faceGeo = match ($face) {
                Facing::NORTH => new ModelGeometry(
                    new TexturePosition(5, 0),
                    new TexturePosition(6, $length)
                ),
                Facing::EAST => new ModelGeometry(
                    new TexturePosition(11, 5),
                    new TexturePosition($length, 6)
                ),
                Facing::SOUTH => new ModelGeometry(
                    new TexturePosition(5, 11),
                    new TexturePosition(6, $length)
                ),
                Facing::WEST => new ModelGeometry(
                    new TexturePosition(0, 5),
                    new TexturePosition($length, 6)
                ),
                default => null
            };

            if ($faceGeo !== null) $geo[] = $faceGeo;
        }

        return $geo;
    }
}