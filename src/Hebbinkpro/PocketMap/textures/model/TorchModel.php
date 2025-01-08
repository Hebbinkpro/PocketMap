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
use pocketmine\block\Torch;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class TorchModel extends HorizontalFacingModel
{

    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        if (!$block instanceof Torch) return parent::getGeometry($block, $chunk);

        $facing = $block->getFacing();
        if (in_array($facing, Facing::HORIZONTAL, true)) {
            return [
                // top
                new ModelGeometry(
                    new TexturePosition(7, 6),
                    TexturePosition::xy(2),
                    new TexturePosition(7, 11)
                ),
                // stick
                new ModelGeometry(
                    new TexturePosition(7, 8),
                    new TexturePosition(2, 8),
                    new TexturePosition(7, 13),
                    new TexturePosition(2, 3)
                )
            ];
        }

        // top in a block center
        return [
            new ModelGeometry(
                new TexturePosition(7, 6),
                TexturePosition::xy(2),
                TexturePosition::center()
            )
        ];
    }

    public function getRotation(Block $block): int
    {
        if (!$block instanceof Torch) return 0;

        return match ($block->getFacing()) {
            Facing::EAST => 270,
            Facing::SOUTH => 180,
            Facing::WEST => 90,
            default => 0 // Facing::NORTH
        };
    }
}