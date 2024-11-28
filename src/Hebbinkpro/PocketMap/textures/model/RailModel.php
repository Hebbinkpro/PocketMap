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
use pocketmine\block\Rail;
use pocketmine\block\StraightOnlyRail;
use pocketmine\data\bedrock\block\BlockLegacyMetadata;

class RailModel extends RotatedBlockModel
{
    /**
     * @inheritDoc
     */
    public function getRotation(Block $block): int
    {
        $shape = BlockLegacyMetadata::RAIL_STRAIGHT_NORTH_SOUTH;
        if ($block instanceof Rail || $block instanceof StraightOnlyRail) $shape = $block->getShape();

        return match ($shape) {
            BlockLegacyMetadata::RAIL_STRAIGHT_NORTH_SOUTH, BlockLegacyMetadata::RAIL_ASCENDING_NORTH, BlockLegacyMetadata::RAIL_ASCENDING_SOUTH, BlockLegacyMetadata::RAIL_CURVE_SOUTHEAST, => 0,
            BlockLegacyMetadata::RAIL_STRAIGHT_EAST_WEST, BlockLegacyMetadata::RAIL_ASCENDING_EAST, BlockLegacyMetadata::RAIL_ASCENDING_WEST, BlockLegacyMetadata::RAIL_CURVE_NORTHEAST => 90,
            BlockLegacyMetadata::RAIL_CURVE_SOUTHWEST => 270,
            BlockLegacyMetadata::RAIL_CURVE_NORTHWEST => 180,

        };
    }
}