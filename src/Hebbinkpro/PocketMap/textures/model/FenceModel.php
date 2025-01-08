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
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class FenceModel extends ConnectionModel
{
    /**
     * @inheritDoc
     */
    public function getCenterGeometry(Block $block): array
    {
        return [
            ModelGeometry::fromCenter(4)
        ];
    }

    /**
     * @inheritDoc
     */
    public function getConnectionsGeometry(Block $block, Chunk $chunk): array
    {
        $geo = [];

        $connections = BlockUtils::getConnections($block, $chunk);

        foreach ($connections as $face) {
            $faceGeo = match ($face) {
                Facing::NORTH => new ModelGeometry(
                    new TexturePosition(7, 0),
                    new TexturePosition(2, 6)
                ),
                Facing::EAST => new ModelGeometry(
                    new TexturePosition(10, 7),
                    new TexturePosition(6, 2)
                ),
                Facing::SOUTH => new ModelGeometry(
                    new TexturePosition(7, 10),
                    new TexturePosition(2, 6)
                ),
                Facing::WEST => new ModelGeometry(
                    new TexturePosition(0, 7),
                    new TexturePosition(6, 2)
                ),
                default => null
            };

            if ($faceGeo !== null) $geo[] = $faceGeo;
        }

        return $geo;
    }
}