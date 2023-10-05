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

use pocketmine\block\BaseCake;
use pocketmine\block\BaseCoral;
use pocketmine\block\BaseFire;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Candle;
use pocketmine\block\Chest;
use pocketmine\block\Crops;
use pocketmine\block\Fence;
use pocketmine\block\Flower;
use pocketmine\block\GlassPane;
use pocketmine\block\PressurePlate;
use pocketmine\block\Sapling;
use pocketmine\block\ShulkerBox;
use pocketmine\block\Stair;
use pocketmine\block\TallGrass;
use pocketmine\block\Thin;
use pocketmine\block\Torch;
use pocketmine\block\utils\AnyFacingTrait;
use pocketmine\block\utils\ColoredTrait;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\block\utils\PillarRotationTrait;
use pocketmine\block\utils\SignLikeRotationTrait;
use pocketmine\block\Wall;
use ReflectionClass;
use ReflectionException;


class BlockUtils
{
    public static function isHidden(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::AIR, BlockTypeIds::BARRIER => true,
            default => false
        };
    }

    public static function hasModel(Block $block): bool
    {
        return self::hasCrossModel($block) || self::hasConnections($block) || self::hasStickModel($block)
            || self::isNotFullBlock($block) || self::hasHorizontalFacing($block) || self::hasSignLikeRotation($block)
            || self::hasAnyFacing($block);
    }

    public static function hasCrossModel(Block $block): bool
    {
        if (self::isGrass($block) || self::isSapling($block) || self::isCrops($block) || self::isFlower($block)
            || self::isCoral($block) || self::isFungi($block) || self::isVines($block) || self::isFire($block)) return true;

        return match ($block->getTypeId()) {
            BlockTypeIds::CHAIN, BlockTypeIds::BIG_DRIPLEAF_STEM, BlockTypeIds::BREWING_STAND, BlockTypeIds::COBWEB,
            BlockTypeIds::SWEET_BERRY_BUSH, BlockTypeIds::DEAD_BUSH, BlockTypeIds::HANGING_ROOTS => true,
            default => false
        };
    }

    public static function isGrass(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::TALL_GRASS, BlockTypeIds::DOUBLE_TALLGRASS, BlockTypeIds::FERN, BlockTypeIds::LARGE_FERN => true,
            default => $block instanceof TallGrass
        };
    }

    public static function isSapling(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::OAK_SAPLING, BlockTypeIds::BIRCH_SAPLING, BlockTypeIds::SPRUCE_SAPLING, BlockTypeIds::ACACIA_SAPLING,
            BlockTypeIds::DARK_OAK_SAPLING, BlockTypeIds::JUNGLE_SAPLING, BlockTypeIds::CHERRY_SAPLING => true,
            default => $block instanceof Sapling
        };
    }

    public static function isCrops(Block $block): bool
    {

        return match ($block->getTypeId()) {
            BlockTypeIds::WHEAT, BlockTypeIds::BEETROOTS, BlockTypeIds::CARROTS, BlockTypeIds::POTATOES,
            BlockTypeIds::MELON_STEM, BlockTypeIds::PUMPKIN_STEM => true,
            default => $block instanceof Crops
        };
    }

    public static function isFlower(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::DANDELION, BlockTypeIds::POPPY, BlockTypeIds::BLUE_ORCHID, BlockTypeIds::ALLIUM,
            BlockTypeIds::AZURE_BLUET, BlockTypeIds::RED_TULIP, BlockTypeIds::ORANGE_TULIP, BlockTypeIds::WHITE_TULIP,
            BlockTypeIds::PINK_TULIP, BlockTypeIds::OXEYE_DAISY, BlockTypeIds::CORNFLOWER, BlockTypeIds::LILY_OF_THE_VALLEY,
            BlockTypeIds::WITHER_ROSE, BlockTypeIds::SUNFLOWER, BlockTypeIds::LILAC, BlockTypeIds::ROSE_BUSH,
            BlockTypeIds::PEONY => true,
            default => $block instanceof Flower
        };
    }

    public static function isCoral(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::CORAL, BlockTypeIds::CORAL_FAN, BlockTypeIds::WALL_CORAL_FAN => true,
            default => $block instanceof BaseCoral
        };
    }

    public static function isFungi(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::RED_MUSHROOM, BlockTypeIds::BROWN_MUSHROOM => true,
            default => false
        };
    }

    public static function isVines(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::CAVE_VINES, BlockTypeIds::TWISTING_VINES, BlockTypeIds::WEEPING_VINES => true,
            default => false
        };
    }

    public static function isFire(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::FIRE, BlockTypeIds::SOUL_FIRE => true,
            default => $block instanceof BaseFire
        };
    }

    public static function hasConnections(Block $block): bool
    {
        if (self::isFence($block) || self::isThin($block) || self::isWall($block)) return true;

        return false;
    }

    public static function hasPost(Block $block): bool
    {
        return self::isWall($block);
    }

    /**
     * @param Block $block
     * @return array
     */
    public static function getConnections(Block $block): array
    {
        if (!self::hasConnections($block)) return [];

        /** @var Fence|GlassPane|Wall $block */
        return $block->getConnections();
    }

    public static function isFence(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::OAK_FENCE, BlockTypeIds::SPRUCE_FENCE, BlockTypeIds::BIRCH_FENCE, BlockTypeIds::JUNGLE_FENCE,
            BlockTypeIds::ACACIA_FENCE, BlockTypeIds::DARK_OAK_FENCE, BlockTypeIds::MANGROVE_FENCE, BlockTypeIds::CHERRY_FENCE,
            BlockTypeIds::CRIMSON_FENCE, BlockTypeIds::WARPED_FENCE, BlockTypeIds::NETHER_BRICK_FENCE => true,
            default => $block instanceof Fence
        };
    }

    public static function isThin(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::GLASS_PANE, BlockTypeIds::HARDENED_GLASS_PANE, BlockTypeIds::STAINED_HARDENED_GLASS_PANE, BlockTypeIds::STAINED_GLASS_PANE,
            BlockTypeIds::IRON_BARS => true,
            default => $block instanceof Thin
        };
    }

    public static function isWall(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::COBBLESTONE_WALL, BlockTypeIds::MOSSY_COBBLESTONE_WALL, BlockTypeIds::STONE_BRICK_WALL, BlockTypeIds::MOSSY_STONE_BRICK_WALL,
            BlockTypeIds::ANDESITE_WALL, BlockTypeIds::DIORITE_WALL, BlockTypeIds::GRANITE_WALL, BlockTypeIds::SANDSTONE_WALL,
            BlockTypeIds::RED_SANDSTONE_WALL, BlockTypeIds::BRICK_WALL, BlockTypeIds::PRISMARINE_WALL, BlockTypeIds::NETHER_BRICK_WALL,
            BlockTypeIds::RED_NETHER_BRICK_WALL, BlockTypeIds::END_STONE_BRICK_WALL, BlockTypeIds::BLACKSTONE_WALL, BlockTypeIds::POLISHED_BLACKSTONE_WALL,
            BlockTypeIds::POLISHED_BLACKSTONE_BRICK_WALL, BlockTypeIds::COBBLED_DEEPSLATE_WALL, BlockTypeIds::POLISHED_DEEPSLATE_WALL,
            BlockTypeIds::DEEPSLATE_BRICK_WALL, BlockTypeIds::DEEPSLATE_TILE_WALL, BlockTypeIds::MUD_BRICK_WALL => true,
            default => false
        };
    }

    public static function hasStickModel(Block $block): bool
    {
        if (self::isTorch($block) || self::isCandle($block) || self::isBamboo($block)) return true;

        return match ($block->getTypeId()) {
            BlockTypeIds::LIGHTNING_ROD, BlockTypeIds::END_ROD => true,
            default => false
        };
    }

    public static function isTorch(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::TORCH, BlockTypeIds::SOUL_TORCH, BlockTypeIds::REDSTONE_TORCH, BlockTypeIds::UNDERWATER_TORCH,
            BlockTypeIds::BLUE_TORCH, BlockTypeIds::RED_TORCH, BlockTypeIds::PURPLE_TORCH, BlockTypeIds::GREEN_TORCH => true,
            default => $block instanceof Torch
        };
    }

    public static function isCandle(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::CANDLE, BlockTypeIds::DYED_CANDLE => true,
            default => $block instanceof Candle
        };
    }

    public static function isBamboo(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::BAMBOO, BlockTypeIds::BAMBOO_SAPLING => true,
            default => false
        };
    }

    public static function isNotFullBlock(Block $block): bool
    {
        if (self::isChest($block) || self::isPressurePlate($block) || self::isCake($block)
            || self::isStairs($block) || self::isShulker($block)) return true;

        return false;
    }

    public static function isChest(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::CHEST, BlockTypeIds::TRAPPED_CHEST, BlockTypeIds::ENDER_CHEST => true,
            default => $block instanceof Chest
        };
    }

    public static function isPressurePlate(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::WEIGHTED_PRESSURE_PLATE_LIGHT, BlockTypeIds::WEIGHTED_PRESSURE_PLATE_HEAVY, BlockTypeIds::STONE_PRESSURE_PLATE,
            BlockTypeIds::POLISHED_BLACKSTONE_PRESSURE_PLATE, BlockTypeIds::OAK_PRESSURE_PLATE, BlockTypeIds::SPRUCE_PRESSURE_PLATE,
            BlockTypeIds::BIRCH_PRESSURE_PLATE, BlockTypeIds::JUNGLE_PRESSURE_PLATE, BlockTypeIds::ACACIA_PRESSURE_PLATE,
            BlockTypeIds::DARK_OAK_PRESSURE_PLATE, BlockTypeIds::MANGROVE_PRESSURE_PLATE, BlockTypeIds::CHERRY_PRESSURE_PLATE,
            BlockTypeIds::CRIMSON_PRESSURE_PLATE, BlockTypeIds::WARPED_PRESSURE_PLATE => true,
            default => $block instanceof PressurePlate
        };
    }

    public static function isCake(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::CAKE, BlockTypeIds::CAKE_WITH_CANDLE, BlockTypeIds::CAKE_WITH_DYED_CANDLE => true,
            default => $block instanceof BaseCake
        };
    }

    public static function hasHorizontalFacing(Block $block): bool
    {
        return in_array(HorizontalFacingTrait::class, self::getTraits($block));
    }

    public static function hasSignLikeRotation(Block $block): bool
    {
        return in_array(SignLikeRotationTrait::class, self::getTraits($block));
    }

    public static function hasAnyFacing(Block $block): bool
    {
        if (in_array(AnyFacingTrait::class, self::getTraits($block))) return true;

        return match ($block->getTypeId()) {
            BlockTypeIds::LEVER => true,
            default => false
        };
    }

    public static function hasPillarRotation(Block $block): bool
    {
        return in_array(PillarRotationTrait::class, self::getTraits($block));
    }

    public static function hasColor(Block $block): bool
    {
        return in_array(ColoredTrait::class, self::getTraits($block));
    }

    public static function isStairs(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::OAK_STAIRS, BlockTypeIds::SPRUCE_STAIRS, BlockTypeIds::BIRCH_STAIRS, BlockTypeIds::JUNGLE_STAIRS,
            BlockTypeIds::ACACIA_STAIRS, BlockTypeIds::DARK_OAK_STAIRS, BlockTypeIds::MANGROVE_STAIRS, BlockTypeIds::CHERRY_STAIRS,
            BlockTypeIds::CRIMSON_STAIRS, BlockTypeIds::WARPED_STAIRS, BlockTypeIds::STONE_STAIRS, BlockTypeIds::GRANITE_STAIRS,
            BlockTypeIds::POLISHED_GRANITE_STAIRS, BlockTypeIds::DIORITE_STAIRS, BlockTypeIds::POLISHED_DIORITE_STAIRS,
            BlockTypeIds::ANDESITE_STAIRS, BlockTypeIds::POLISHED_ANDESITE_STAIRS, BlockTypeIds::COBBLESTONE_STAIRS,
            BlockTypeIds::MOSSY_COBBLESTONE_STAIRS, BlockTypeIds::STONE_BRICK_STAIRS, BlockTypeIds::MOSSY_STONE_BRICK_STAIRS,
            BlockTypeIds::BRICK_STAIRS, BlockTypeIds::END_STONE_BRICK_STAIRS, BlockTypeIds::NETHER_BRICK_STAIRS,
            BlockTypeIds::RED_NETHER_BRICK_STAIRS, BlockTypeIds::SANDSTONE_STAIRS, BlockTypeIds::SMOOTH_SANDSTONE_STAIRS,
            BlockTypeIds::SMOOTH_RED_SANDSTONE_STAIRS, BlockTypeIds::RED_SANDSTONE_STAIRS, BlockTypeIds::QUARTZ_STAIRS,
            BlockTypeIds::SMOOTH_QUARTZ_STAIRS, BlockTypeIds::PURPUR_STAIRS, BlockTypeIds::PRISMARINE_STAIRS,
            BlockTypeIds::PRISMARINE_BRICKS_STAIRS, BlockTypeIds::BLACKSTONE_STAIRS, BlockTypeIds::POLISHED_BLACKSTONE_STAIRS,
            BlockTypeIds::POLISHED_BLACKSTONE_BRICK_STAIRS, BlockTypeIds::CUT_COPPER_STAIRS, BlockTypeIds::COBBLED_DEEPSLATE_STAIRS,
            BlockTypeIds::POLISHED_DEEPSLATE_STAIRS, BlockTypeIds::DEEPSLATE_BRICK_STAIRS, BlockTypeIds::DEEPSLATE_TILE_STAIRS,
            BlockTypeIds::MUD_BRICK_STAIRS => true,
            default => $block instanceof Stair
        };
    }

    public static function isShulker(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::SHULKER_BOX, BlockTypeIds::DYED_SHULKER_BOX => true,
            default => $block instanceof ShulkerBox
        };
    }

    /**
     * Get a list of all traits a block (including traits in its parent classes) is using
     * @param Block $block
     * @return array the list of all traits
     */
    public static function getTraits(Block $block): array
    {

        try {
            $reflection = new ReflectionClass($block::class);
        } catch (ReflectionException) {
            return [];
        }

        $traits = array_keys($reflection->getTraits());
        while ($reflection->getParentClass() !== false) {
            $reflection = $reflection->getParentClass();
            $traits = array_merge($traits, array_keys($reflection->getTraits()));
        }

        return array_unique($traits);
    }
}