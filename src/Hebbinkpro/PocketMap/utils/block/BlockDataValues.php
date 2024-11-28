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

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\CakeWithDyedCandle;
use pocketmine\block\Candle;
use pocketmine\block\ChemistryTable;
use pocketmine\block\CoralBlock;
use pocketmine\block\Crops;
use pocketmine\block\DaylightSensor;
use pocketmine\block\Door;
use pocketmine\block\DoublePitcherCrop;
use pocketmine\block\DoublePlant;
use pocketmine\block\DyedCandle;
use pocketmine\block\Farmland;
use pocketmine\block\FloorBanner;
use pocketmine\block\Hopper;
use pocketmine\block\Leaves;
use pocketmine\block\PitcherCrop;
use pocketmine\block\RedMushroomBlock;
use pocketmine\block\TorchflowerCrop;
use pocketmine\block\utils\CoralType;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\utils\LeavesType;
use pocketmine\block\utils\WoodType;
use pocketmine\block\WallBanner;
use pocketmine\block\WoodenDoor;
use pocketmine\block\WoodenSlab;
use pocketmine\block\Wool;
use pocketmine\data\bedrock\block\BlockStateNames as BSN;
use pocketmine\data\bedrock\block\BlockTypeNames as BTN;
use pocketmine\data\bedrock\DyeColorIdMap;

/**
 * Class that maps all blocks to their data values in the bedrock code.
 * These data values are still used e.g. resource pack textures
 */
final class BlockDataValues
{
    /**
     * Get the data value of a given block
     * @param Block $block the block to get the data value of
     * @return int the data value of the block
     */
    public static function getDataValue(Block $block): int
    {
        $bsd = BlockStateParser::getBlockStateData($block);
        if ($bsd === null) return 0;
        $name = $bsd->getName();

        // list with all blocks using a colored trait, but not using it in their texture as data value
        $coloredWithoutColor = [WallBanner::class, FloorBanner::class, DyedCandle::class, CakeWithDyedCandle::class];

        // block uses the ColoredTrait, so it's colored
        if (BlockUtils::hasColor($block) && !in_array($block::class, $coloredWithoutColor, true)) {
            // get the color of the block
            /** @var Wool $block */
            $color = $block->getColor();

            // return the id of the color
            return self::getColorDataValue($color);
        }

        if (BlockUtils::isPoweredByRedstone($block)) {
            /** @var Hopper $block */
            return intval($block->isPowered());
        }

        // go through the switch to get the correct data values
        switch ($block::class) {
            case Candle::class:
                return intval($block->isLit());

            case ChemistryTable::class:
                return self::getChemistryDataValue($block->getTypeId());

            case Crops::class:
            case PitcherCrop::class:
                return $block->getAge();

            case TorchflowerCrop::class:
                return intval($block->isReady());

            case DoublePitcherCrop::class:
                return $block->getAge() + 1 + PitcherCrop::MAX_AGE;

            case WoodenDoor::class:
                // use the wood type to determine the index
                return self::getWoodDataValue($block->getWoodType());

            case Door::class:
                // iron door is in the same list as all the wood doors
                if ($name === BTN::IRON_DOOR) return 6;
                return 0;

            case DoublePlant::class:
                // pitcher plant uses pitcher_crop stage 4 texture
                if ($name === BTN::PITCHER_PLANT) return 4;

                return self::getDoublePlantDataValue($block->getTypeId());

            case Leaves::class:
                return self::getLeavesDataValue($block->getLeavesType());

            case RedMushroomBlock::class:
                /** @var int|null $state */
                $state = BlockStateParser::getStateValue($bsd, BSN::HUGE_MUSHROOM_BITS);
                return $state ?? 0;

            case WoodenSlab::class:
                return self::getWoodDataValue($block->getWoodType());


            case CoralBlock::class:
                return self::getCoralDataValue($block->getCoralType());

            case Farmland::class:
                if ($block->getWetness() > 0) return 0;
                return 1;

            case DaylightSensor::class:
                return intval($block->isInverted());

            default:
                return 0;
        }
    }

    /**
     * Get the color data value of a block (0-15)
     * @param DyeColor $color the color to get the value of
     * @return int the color id
     */
    public static function getColorDataValue(DyeColor $color): int
    {
        return DyeColorIdMap::getInstance()->toId($color);

    }

    public static function getChemistryDataValue(int $blockId): int
    {

        return match ($blockId) {
            BlockTypeIds::COMPOUND_CREATOR => 0,
            BlockTypeIds::MATERIAL_REDUCER => 1,
            BlockTypeIds::ELEMENT_CONSTRUCTOR => 2,
            BlockTypeIds::LAB_TABLE => 3,
            default => 0
        };
    }

    /**
     * Get the wood type data value
     * @param WoodType $wood
     * @return int
     */
    public static function getWoodDataValue(WoodType $wood): int
    {
        return match ($wood) {
            WoodType::OAK => 0,
            WoodType::SPRUCE => 1,
            WoodType::BIRCH => 2,
            WoodType::JUNGLE => 3,
            WoodType::ACACIA => 4,
            WoodType::DARK_OAK => 5,
            default => 0
        };
    }

    public static function getDoublePlantDataValue(int $blockId): int
    {
        return match ($blockId) {
            BlockTypeIds::SUNFLOWER => 0,
            BlockTypeIds::LILAC => 1, // SYRINGA
            BlockTypeIds::GRASS => 2,
            BlockTypeIds::FERN => 3,
            BlockTypeIds::ROSE_BUSH => 4, // ROSE
            BlockTypeIds::PEONY => 5, // PAEONIA
            default => 0
        };
    }

    /**
     * Get the leave's type data value
     * @param LeavesType $wood
     * @return int
     */
    public static function getLeavesDataValue(LeavesType $wood): int
    {
        return match ($wood) {
            LeavesType::OAK => 0,
            LeavesType::SPRUCE => 1,
            LeavesType::BIRCH => 2,
            LeavesType::JUNGLE => 3,
            LeavesType::ACACIA => 0,
            LeavesType::DARK_OAK => 1,
            default => 0
        };
    }

    public static function getCoralDataValue(CoralType $coral): int
    {
        return match ($coral) {
            CoralType::TUBE => 0,
            CoralType::BRAIN => 1,
            CoralType::BUBBLE => 2,
            CoralType::FIRE => 3,
            CoralType::HORN => 5
        };
    }

}