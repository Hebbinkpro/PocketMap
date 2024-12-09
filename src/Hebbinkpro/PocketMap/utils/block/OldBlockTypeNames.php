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

namespace Hebbinkpro\PocketMap\utils\block;

use pocketmine\data\bedrock\block\BlockTypeNames as BTN;

/**
 * Class containing all block type names that are removed/replaced in PMMP but still used in the blocks.json in the Minecraft resource packs.
 */
final class OldBlockTypeNames
{
    public const GRASS = "minecraft:grass";

    /**
     * Get the type name of a block.
     * If the type name has an old type name, the old type name will be returned,
     * otherwise the same name will be returned
     * @param string $typeName
     * @return string the same type name or the old type name
     */
    public static function getTypeName(string $typeName): string
    {

        // other type names that should be converted, or return the type name by default
        return match ($typeName) {
            // leaves are still split between leaves and leaves2

            // grass_block is named grass
            BTN::GRASS_BLOCK => self::GRASS,

            // some stone double slabs do not exist
            BTN::CUT_RED_SANDSTONE_DOUBLE_SLAB => BTN::CUT_RED_SANDSTONE_SLAB,
            BTN::CUT_SANDSTONE_DOUBLE_SLAB => BTN::CUT_SANDSTONE_SLAB,
            BTN::MOSSY_STONE_BRICK_DOUBLE_SLAB => BTN::MOSSY_STONE_BRICK_SLAB,
            BTN::SMOOTH_QUARTZ_DOUBLE_SLAB => BTN::SMOOTH_QUARTZ_SLAB,
            BTN::NORMAL_STONE_DOUBLE_SLAB => BTN::NORMAL_STONE_SLAB,

            default => $typeName
        };
    }
}