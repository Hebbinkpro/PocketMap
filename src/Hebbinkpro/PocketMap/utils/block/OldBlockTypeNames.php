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

use Hebbinkpro\PocketMap\utils\block\OldBlockTypeNames as OBTN;
use pocketmine\data\bedrock\block\BlockTypeNames as BTN;

/**
 * Class containing all block type names that are removed/replaced in PMMP but still used in the blocks.json in the Minecraft resource packs.
 */
final class OldBlockTypeNames
{
    public const LOG = "minecraft:log";
    public const LOG2 = "minecraft:log2";
    public const CONCRETE = "minecraft:concrete";
    public const CONCRETE_POWDER = "minecraft:concretePowder";

    /**
     * Get the type name of a block.
     * If the type name has an old type name, the old type name will be returned,
     * otherwise the same name will be returned
     * @param string $typeName
     * @return string the same type name or the old type name
     */
    public static function getTypeName(string $typeName): string
    {
        return match ($typeName) {
            BTN::OAK_LOG, BTN::BIRCH_LOG, BTN::SPRUCE_LOG, BTN::JUNGLE_LOG
            => OBTN::LOG,
            BTN::ACACIA_LOG, BTN::DARK_OAK_LOG
            => OBTN::LOG2,

            BTN::WHITE_CONCRETE, BTN::ORANGE_CONCRETE, BTN::MAGENTA_CONCRETE, BTN::LIGHT_BLUE_CONCRETE,
            BTN::YELLOW_CONCRETE, BTN::LIME_CONCRETE, BTN::PINK_CONCRETE, BTN::GRAY_CONCRETE,
            BTN::LIGHT_GRAY_CONCRETE, BTN::CYAN_CONCRETE, BTN::PURPLE_CONCRETE, BTN::BLUE_CONCRETE,
            BTN::BROWN_CONCRETE, BTN::GREEN_CONCRETE, BTN::RED_CONCRETE, BTN::BLACK_CONCRETE
            => OBTN::CONCRETE,

            BTN::WHITE_CONCRETE_POWDER, BTN::ORANGE_CONCRETE_POWDER, BTN::MAGENTA_CONCRETE_POWDER, BTN::LIGHT_BLUE_CONCRETE_POWDER,
            BTN::YELLOW_CONCRETE_POWDER, BTN::LIME_CONCRETE_POWDER, BTN::PINK_CONCRETE_POWDER, BTN::GRAY_CONCRETE_POWDER,
            BTN::LIGHT_GRAY_CONCRETE_POWDER, BTN::CYAN_CONCRETE_POWDER, BTN::PURPLE_CONCRETE_POWDER, BTN::BLUE_CONCRETE_POWDER,
            BTN::BROWN_CONCRETE_POWDER, BTN::GREEN_CONCRETE_POWDER, BTN::RED_CONCRETE_POWDER, BTN::BLACK_CONCRETE_POWDER
            => OBTN::CONCRETE_POWDER,

            // just return the name
            default => $typeName
        };
    }
}