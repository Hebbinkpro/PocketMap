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
use pocketmine\block\Button;
use pocketmine\math\Facing;

class AnyFacingModel extends RotatedBlockModel
{

    /**
     * @inheritDoc
     */
    public function getRotation(Block $block): int
    {
        if (!BlockUtils::hasAnyFacing($block)) return 0;

        /** @var Button $block */
        return match ($block->getFacing()) {
            Facing::DOWN, Facing::UP, Facing::NORTH => 0,
            Facing::EAST => 270,
            Facing::SOUTH => 180,
            Facing::WEST => 90,
        };
    }
}