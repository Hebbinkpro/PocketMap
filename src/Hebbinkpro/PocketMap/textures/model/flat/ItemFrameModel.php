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

namespace Hebbinkpro\PocketMap\textures\model\flat;

use pocketmine\block\Block;
use pocketmine\block\ItemFrame;
use pocketmine\world\format\Chunk;

/**
 * TODO the item frame uses the oak_planks texture for its edges
 */
class ItemFrameModel extends MultiAnyFacingModel
{
    public function getFaces(Block $block): array
    {
        if (!$block instanceof ItemFrame) return [];

        return [$block->getFacing()];

    }

    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        if (!$block instanceof ItemFrame) return null;

        // item frames do not have an inverted facing
        return self::square(0, $this->getFaces($block));
    }
}