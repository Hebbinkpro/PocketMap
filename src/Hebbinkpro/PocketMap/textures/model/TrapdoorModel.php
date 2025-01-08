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
use pocketmine\block\FenceGate;
use pocketmine\block\Trapdoor;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class TrapdoorModel extends HorizontalFacingModel
{
    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        if (!$block instanceof Trapdoor) return null;

        if ($block->isOpen()) {
            return [
                new ModelGeometry(
                    TexturePosition::zero(),
                    new TexturePosition(16, 3)
                )
            ];
        }

        return self::getDefaultGeometry();
    }

    public function getRotation(Block $block): int
    {
        if (!BlockUtils::hasHorizontalFacing($block)) return 0;
        /** @var FenceGate $block */

        // trapdoor facings have a different north/south rotation, because why not...
        return match ($block->getFacing()) {
            Facing::EAST => 270,
            Facing::NORTH => 180,
            Facing::WEST => 90,
            default => 0 // Facing::SOUTH
        };
    }
}