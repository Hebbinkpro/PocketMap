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
use pocketmine\block\ItemFrame;
use pocketmine\math\Facing;

/**
 * TODO the item frame uses the oak_planks texture for its edges
 */
class ItemFrameModel extends MultiAnyFacingModel
{
    public function getFaces(Block $block): array
    {
        if (!$block instanceof ItemFrame) return [];

        $facing = $block->getFacing();

        $newFacing = match ($facing) {
            Facing::UP => Facing::DOWN,
            Facing::DOWN => Facing::UP,
            Facing::NORTH => Facing::SOUTH,
            Facing::EAST => Facing::WEST,
            Facing::WEST => Facing::NORTH,
            Facing::SOUTH => Facing::EAST,
        };

        return [$newFacing];
    }
}