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
    public const LEAVES = "minecraft:leaves";
    public const LEAVES2 = "minecraft:leaves2";
    public const WOOD = "minecraft:wood";
    public const WOODEN_SLAB = "minecraft:wooden_slab";
    public const DOUBLE_WOODEN_SLAB = "minecraft:double_wooden_slab";
    public const GRASS = "minecraft:grass";

    public const CHAIN = "minecraft:chain";

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
            BTN::OAK_WOOD, BTN::BIRCH_WOOD, BTN::SPRUCE_WOOD, BTN::JUNGLE_WOOD, BTN::ACACIA_WOOD, BTN::DARK_OAK_WOOD,
            BTN::STRIPPED_OAK_WOOD, BTN::STRIPPED_BIRCH_WOOD, BTN::STRIPPED_SPRUCE_WOOD, BTN::STRIPPED_JUNGLE_WOOD,
            BTN::STRIPPED_ACACIA_WOOD, BTN::STRIPPED_DARK_OAK_WOOD
            => self::WOOD,

            BTN::OAK_LEAVES, BTN::BIRCH_LEAVES, BTN::SPRUCE_LEAVES, BTN::JUNGLE_LEAVES
            => self::LEAVES,

            BTN::ACACIA_LEAVES, BTN::DARK_OAK_LEAVES
            => self::LEAVES2,

            BTN::OAK_SLAB, BTN::BIRCH_SLAB, BTN::SPRUCE_SLAB, BTN::JUNGLE_SLAB, BTN::ACACIA_SLAB, BTN::DARK_OAK_SLAB
            => self::WOODEN_SLAB,

            BTN::OAK_DOUBLE_SLAB, BTN::BIRCH_DOUBLE_SLAB, BTN::SPRUCE_DOUBLE_SLAB, BTN::JUNGLE_DOUBLE_SLAB,
            BTN::ACACIA_DOUBLE_SLAB, BTN::DARK_OAK_DOUBLE_SLAB
            => self::DOUBLE_WOODEN_SLAB,

            BTN::GRASS_BLOCK => self::GRASS,

            BTN::IRON_CHAIN => self::CHAIN,

            default => $typeName
        };
    }

    /**
     * @param string $coloredTypeName The type name from a block
     * @param string $typeName The type name that has to be at the end of the colored type name
     * @param string $oldTypeName The type name that should replace the colored type name
     * @param array<string> $exceptions Type names that end with the same type name, but should not be replaced
     * @return string|null the old type name, or null when it doesn't match
     * @deprecated currently not needed, but you never know what Mojang is going to do
     */
    public static function getColoredTypeName(string $coloredTypeName, string $typeName, string $oldTypeName, array $exceptions = []): ?string
    {
        if (!str_ends_with($coloredTypeName, strtolower($typeName))) return null;

        foreach ($exceptions as $exception) {
            if (str_ends_with($coloredTypeName, strtolower($exception))) return null;
        }

        return $oldTypeName;
    }
}