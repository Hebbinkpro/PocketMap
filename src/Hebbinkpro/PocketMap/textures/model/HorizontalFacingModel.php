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

use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use pocketmine\block\Block;
use pocketmine\block\FenceGate;
use pocketmine\math\Facing;

class HorizontalFacingModel extends RotatedBlockModel
{
    public function getRotation(Block $block): int
    {
        if (!BlockUtils::hasHorizontalFacing($block)) return 0;
        /** @var FenceGate $block */

        return match ($block->getFacing()) {
            Facing::EAST => 270,
            Facing::SOUTH => 180,
            Facing::WEST => 90,
            default => 0 // Facing::NORTH
        };
    }
}