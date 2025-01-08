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
use pocketmine\block\LightningRod;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class LightningRodModel extends AnyFacingModel
{
    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        if (!$block instanceof LightningRod) return parent::getGeometry($block, $chunk);

        // 4x4 top
        $top = new ModelGeometry(
            TexturePosition::zero(),
            TexturePosition::xy(4)
        );

        return match ($block->getFacing()) {
            Facing::DOWN => [
                $top->set(dstStart: TexturePosition::xy(6)),
                // 2x2 bottom
                new ModelGeometry(
                    TexturePosition::zero(),
                    TexturePosition::xy(2),
                    TexturePosition::center()
                )
            ],
            Facing::UP => [
                $top->set(dstStart: TexturePosition::xy(6)),
            ],
            default => [
                // 4x4 top
                $top->set(dstStart: new TexturePosition(6, 0)),
                // 2x15 rod
                new ModelGeometry(
                    new TexturePosition(0, 4),
                    new TexturePosition(2, 12),
                    new TexturePosition(7, 4)
                )
            ]
        };
    }
}