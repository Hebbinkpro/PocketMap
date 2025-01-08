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
use pocketmine\block\EndRod;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class EndRodModel extends AnyFacingModel
{

    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        if (!$block instanceof EndRod) return null;

        // 4x4 bottom
        $bottom = new ModelGeometry(
            srcStart: new TexturePosition(2, 3),
            srcSize: TexturePosition::xy(4),
            dstStart: TexturePosition::xy(6)
        );

        return match ($block->getFacing()) {
            Facing::DOWN => [
                $bottom
            ],
            Facing::UP => [
                $bottom,
                // 2x2 top
                new ModelGeometry(
                    new TexturePosition(2, 0),
                    TexturePosition::xy(2),
                    TexturePosition::center()
                )
            ],
            default => [
                // 4x1 bottom
                new ModelGeometry(
                    TexturePosition::xy(2),
                    new TexturePosition(4, 1),
                    new TexturePosition(6, 15)
                ),
                // 2x15 rod
                new ModelGeometry(
                    TexturePosition::zero(),
                    new TexturePosition(2, 15),
                    new TexturePosition(7, 0)
                )
            ]
        };
    }
}