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
use pocketmine\block\Door;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class DoorModel extends HorizontalFacingModel
{

    public function getRotation(Block $block): int
    {
        if (!($block instanceof Door)) return parent::getRotation($block);

        $open = $block->isOpen() ? ($block->isHingeRight() ? 90 : -90) : 0;

        return match ($block->getFacing()) {
            Facing::NORTH => 270 + $open,
            Facing::EAST => 180 + $open,
            Facing::SOUTH => 90 + $open,
            default => $open, // Facing::WEST
        };
    }

    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        return [
            new ModelGeometry(
                TexturePosition::zero(),
                new TexturePosition(16, 3)
            )
        ];
    }
}