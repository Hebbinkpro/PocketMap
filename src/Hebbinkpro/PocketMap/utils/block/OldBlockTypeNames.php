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
 * Copyright (C) 2023 Hebbinkpro
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
 * @deprecated because of the updated texture pack, this class is not necessary anymore
 */
final class OldBlockTypeNames
{
    public const LOG = "minecraft:log";
    public const LOG2 = "minecraft:log2";
    public const CONCRETE = "minecraft:concrete";
    public const CONCRETE_POWDER = "minecraft:concretePowder";
    public const STAINED_GLASS = "minecraft:stained_glass";
    public const STAINED_GLASS_PANE = "minecraft:stained_glass_pane";
    public const STAINED_HARDENED_CLAY = "minecraft:stained_hardened_clay";
    public const FENCE = "minecraft:fence";
    public const CORAL = "minecraft:coral";
    public const SHULKER_BOX = "minecraft:shulker_box";
    public const PLANKS = "minecraft:planks";

    /**
     * Get the type name of a block.
     * If the type name has an old type name, the old type name will be returned,
     * otherwise the same name will be returned
     * @param string $typeName
     * @return string the same type name or the old type name
     */
    public static function getTypeName(string $typeName): string
    {

        // some colored blocks inside the texture packs use the old naming system
        // because pmmp is slowly adding the new naming system for all colored blocks, this will break these blocks on the map
        // because the new naming system isn't in the texture pack for some reason, so we have to convert them...
        if (($name = self::getColoredTypeName($typeName, "concrete", self::CONCRETE)) !== null) return $name;
        if (($name = self::getColoredTypeName($typeName, "concrete_powder", self::CONCRETE_POWDER)) !== null) return $name;
        if (($name = self::getColoredTypeName($typeName, "terracotta", self::STAINED_HARDENED_CLAY, ["glazed_terracotta"])) !== null) return $name;
        if (($name = self::getColoredTypeName($typeName, "stained_glass", self::STAINED_GLASS)) !== null) return $name;
        if (($name = self::getColoredTypeName($typeName, "stained_glass_pane", self::STAINED_GLASS_PANE)) !== null) return $name;
        if (($name = self::getColoredTypeName($typeName, "coral", self::CORAL)) !== null) return $name;
        if (($name = self::getColoredTypeName($typeName, "shulker_box", self::SHULKER_BOX)) !== null) return $name;

        // other type names that should be converted, or return the type name by default
        return match ($typeName) {
            BTN::OAK_LOG, BTN::BIRCH_LOG, BTN::SPRUCE_LOG, BTN::JUNGLE_LOG
            => self::LOG,
            BTN::ACACIA_LOG, BTN::DARK_OAK_LOG
            => self::LOG2,
            BTN::OAK_FENCE => self::FENCE,
            BTN::PITCHER_PLANT => BTN::PITCHER_CROP,
            BTN::OAK_PLANKS => self::PLANKS,
            default => $typeName
        };
    }

    /**
     * @param string $coloredTypeName The type name from a block
     * @param string $typeName The type name that has to be at the end of the colored type name
     * @param string $oldTypeName The type name that should replace the colored type name
     * @return string|null the old type name, or null when it doesn't match
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