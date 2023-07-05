<?php

namespace Hebbinkpro\PocketMap\utils\block;

use pocketmine\block\Block;
use pocketmine\block\Candle;
use pocketmine\block\ChemistryTable;
use pocketmine\block\Crops;
use pocketmine\block\Door;
use pocketmine\block\DoublePlant;
use pocketmine\block\Flower;
use pocketmine\block\Furnace;
use pocketmine\block\Leaves;
use pocketmine\block\Opaque;
use pocketmine\block\Planks;
use pocketmine\block\RedMushroomBlock;
use pocketmine\block\Sapling;
use pocketmine\block\Slab;
use pocketmine\block\Torch;
use pocketmine\block\Wall;
use pocketmine\block\Wood;
use pocketmine\block\WoodenDoor;
use pocketmine\block\WoodenFence;
use pocketmine\data\bedrock\block\BlockStateNames as BSN;
use pocketmine\data\bedrock\block\BlockStateStringValues as BSV;
use pocketmine\data\bedrock\block\BlockTypeNames as BTN;

/**
 * Class that maps all blocks to their data values in the bedrock code.
 * These data values are still used e.g. resource pack textures
 */
final class BlockDataValues
{
    /**
     * List containing all known data values of blocks.
     * If the block does not exist in the list, the data value is always 0
     * @var int[][]
     */
    public static array $dataValues = [
        BTN::WOOD => [
            BSV::WOOD_TYPE_OAK => 0,
            BSV::WOOD_TYPE_SPRUCE => 1,
            BSV::WOOD_TYPE_BIRCH => 2,
            BSV::WOOD_TYPE_JUNGLE => 3,
            BSV::WOOD_TYPE_ACACIA => 4,
            BSV::WOOD_TYPE_DARK_OAK => 5
        ],
        BTN::CHEMISTRY_TABLE => [
            BSV::CHEMISTRY_TABLE_TYPE_COMPOUND_CREATOR => 0,
            BSV::CHEMISTRY_TABLE_TYPE_MATERIAL_REDUCER => 1,
            BSV::CHEMISTRY_TABLE_TYPE_ELEMENT_CONSTRUCTOR => 2,
            BSV::CHEMISTRY_TABLE_TYPE_LAB_TABLE => 3
        ],
        BTN::DOUBLE_PLANT => [
            BSV::DOUBLE_PLANT_TYPE_SUNFLOWER => 0,
            BSV::DOUBLE_PLANT_TYPE_SYRINGA => 1,
            BSV::DOUBLE_PLANT_TYPE_GRASS => 2,
            BSV::DOUBLE_PLANT_TYPE_FERN => 3,
            BSV::DOUBLE_PLANT_TYPE_ROSE => 4,
            BSV::DOUBLE_PLANT_TYPE_PAEONIA => 5
        ],
        BTN::LEAVES => [
            BSV::OLD_LEAF_TYPE_OAK => 0,
            BSV::OLD_LEAF_TYPE_SPRUCE => 1,
            BSV::OLD_LEAF_TYPE_BIRCH => 2,
            BSV::OLD_LEAF_TYPE_JUNGLE => 3
        ],
        BTN::LEAVES2 => [
            BSV::NEW_LEAF_TYPE_ACACIA => 0,
            BSV::NEW_LEAF_TYPE_DARK_OAK => 1
        ],
        BTN::QUARTZ_BLOCK => [
            BSV::CHISEL_TYPE_DEFAULT => 0,
            BSV::CHISEL_TYPE_CHISELED => 1,
            BSV::CHISEL_TYPE_LINES => 2,
            BSV::CHISEL_TYPE_SMOOTH => 3
        ],
        BTN::RED_FLOWER => [
            BSV::FLOWER_TYPE_POPPY => 0,
            BSV::FLOWER_TYPE_ORCHID => 1,
            BSV::FLOWER_TYPE_ALLIUM => 2,
            BSV::FLOWER_TYPE_HOUSTONIA => 3,
            BSV::FLOWER_TYPE_TULIP_RED => 4,
            BSV::FLOWER_TYPE_TULIP_ORANGE => 5,
            BSV::FLOWER_TYPE_TULIP_WHITE => 6,
            BSV::FLOWER_TYPE_TULIP_PINK => 7,
            BSV::FLOWER_TYPE_OXEYE => 8,
            BSV::FLOWER_TYPE_CORNFLOWER => 9,
            BSV::FLOWER_TYPE_LILY_OF_THE_VALLEY => 10
        ],
        BTN::SANDSTONE => [
            BSV::SAND_STONE_TYPE_DEFAULT => 0,
            BSV::SAND_STONE_TYPE_HEIROGLYPHS => 1,
            BSV::SAND_STONE_TYPE_CUT => 2,
            BSV::SAND_STONE_TYPE_SMOOTH => 3
        ],
        BTN::SAPLING => [
            BSV::SAPLING_TYPE_OAK => 0,
            BSV::SAPLING_TYPE_SPRUCE => 1,
            BSV::SAPLING_TYPE_BIRCH => 2,
            BSV::SAPLING_TYPE_JUNGLE => 3,
            BSV::SAPLING_TYPE_ACACIA => 4,
            BSV::SAPLING_TYPE_DARK_OAK => 5
        ],
        BTN::STONE => [
            BSV::STONE_TYPE_STONE => 0,
            BSV::STONE_TYPE_GRANITE => 1,
            BSV::STONE_TYPE_GRANITE_SMOOTH => 2,
            BSV::STONE_TYPE_DIORITE => 3,
            BSV::STONE_TYPE_DIORITE_SMOOTH => 4,
            BSV::STONE_TYPE_ANDESITE => 5,
            BSV::STONE_TYPE_ANDESITE_SMOOTH => 6
        ],
        BTN::STONEBRICK => [
            BSV::STONE_BRICK_TYPE_DEFAULT => 0,
            BSV::STONE_BRICK_TYPE_MOSSY => 1,
            BSV::STONE_BRICK_TYPE_CRACKED => 2,
            BSV::STONE_BRICK_TYPE_CHISELED => 3
        ],
        BTN::STONE_BLOCK_SLAB => [
            BSV::STONE_SLAB_TYPE_SMOOTH_STONE => 0,
            BSV::STONE_SLAB_TYPE_SANDSTONE => 1,
            BSV::STONE_SLAB_TYPE_WOOD => 2,
            BSV::STONE_SLAB_TYPE_COBBLESTONE => 3,
            BSV::STONE_SLAB_TYPE_BRICK => 4,
            BSV::STONE_SLAB_TYPE_STONE_BRICK => 5,
            BSV::STONE_SLAB_TYPE_QUARTZ => 6,
            BSV::STONE_SLAB_TYPE_NETHER_BRICK => 7
        ],
        BTN::STONE_BLOCK_SLAB2 => [
            BSV::STONE_SLAB_TYPE_2_RED_SANDSTONE => 0,
            BSV::STONE_SLAB_TYPE_2_PURPUR => 1,
            BSV::STONE_SLAB_TYPE_2_PRISMARINE_ROUGH => 2,
            BSV::STONE_SLAB_TYPE_2_PRISMARINE_DARK => 3,
            BSV::STONE_SLAB_TYPE_2_PRISMARINE_BRICK => 4,
            BSV::STONE_SLAB_TYPE_2_MOSSY_COBBLESTONE => 5,
            BSV::STONE_SLAB_TYPE_2_SMOOTH_SANDSTONE => 6,
            BSV::STONE_SLAB_TYPE_2_RED_NETHER_BRICK => 7
        ],
        BTN::STONE_BLOCK_SLAB3 => [
            BSV::STONE_SLAB_TYPE_3_END_STONE_BRICK => 0,
            BSV::STONE_SLAB_TYPE_3_SMOOTH_RED_SANDSTONE => 1,
            BSV::STONE_SLAB_TYPE_3_POLISHED_ANDESITE => 2,
            BSV::STONE_SLAB_TYPE_3_ANDESITE => 3,
            BSV::STONE_SLAB_TYPE_3_DIORITE => 4,
            BSV::STONE_SLAB_TYPE_3_POLISHED_DIORITE => 5,
            BSV::STONE_SLAB_TYPE_3_GRANITE => 6,
            BSV::STONE_SLAB_TYPE_3_POLISHED_GRANITE => 7
        ],
        BTN::STONE_BLOCK_SLAB4 => [
            BSV::STONE_SLAB_TYPE_4_MOSSY_STONE_BRICK => 0,
            BSV::STONE_SLAB_TYPE_4_SMOOTH_QUARTZ => 1,
            BSV::STONE_SLAB_TYPE_4_STONE => 2,
            BSV::STONE_SLAB_TYPE_4_CUT_SANDSTONE => 3,
            BSV::STONE_SLAB_TYPE_4_CUT_RED_SANDSTONE => 4
        ],
        BTN::COBBLESTONE_WALL => [
            BSV::WALL_BLOCK_TYPE_COBBLESTONE => 0,
            BSV::WALL_BLOCK_TYPE_MOSSY_COBBLESTONE => 1,
            BSV::WALL_BLOCK_TYPE_GRANITE => 2,
            BSV::WALL_BLOCK_TYPE_DIORITE => 3,
            BSV::WALL_BLOCK_TYPE_ANDESITE => 4,
            BSV::WALL_BLOCK_TYPE_SANDSTONE => 5,
            BSV::WALL_BLOCK_TYPE_BRICK => 6,
            BSV::WALL_BLOCK_TYPE_STONE_BRICK => 7,
            BSV::WALL_BLOCK_TYPE_MOSSY_STONE_BRICK => 8,
            BSV::WALL_BLOCK_TYPE_NETHER_BRICK => 9,
            BSV::WALL_BLOCK_TYPE_END_BRICK => 10,
            BSV::WALL_BLOCK_TYPE_PRISMARINE => 11,
            BSV::WALL_BLOCK_TYPE_RED_SANDSTONE => 12,
            BSV::WALL_BLOCK_TYPE_RED_NETHER_BRICK => 13
        ]
    ];

    /**
     * Get the data value of a given block
     * @param Block $block the block to get the data value of
     * @return int the data value of the block
     */
    public static function getDataValue(Block $block): int
    {
        $bsd = BlockStateParser::getBlockStateData($block);
        $name = $bsd->getName();

        switch ($block::class) {
            case Planks::class:
            case WoodenDoor::class:
            case WoodenFence::class:
                // get the wood type data value, if the wood type is not from the legacy kind, its just 0
                return self::$dataValues[BTN::WOOD][$block->getWoodType()->name()] ?? 0;

            case Wood::class:
                if ($name === BTN::WOOD) {
                    // it's wood, lots of shit in terrain_textures
                    return self::$dataValues[BTN::WOOD][$block->getWoodType()->name()] * 2 + intval($block->isStripped());
                }

                if (!$block->isStripped()) {
                    // TODO remove this when the texture pack supports it
                    return self::$dataValues[BTN::WOOD][$block->getWoodType()->name()] % 4;
                }
                return 0;


            case Candle::class:
                return intval($block->isLit());

            case ChemistryTable::class:
                return self::$dataValues[BTN::CHEMISTRY_TABLE][BlockStateParser::getStateValue($bsd, BSN::CHEMISTRY_TABLE_TYPE)];

            case Crops::class:
                return $block->getAge();

            case Torch::class:
                return self::$dataValues[BTN::TORCH][BlockStateParser::getStateValue($bsd, BSN::COLOR_BIT)];

            case Door::class:
                // iron door does not count towards wood but has a data value, so check if it's iron and else return 0
                if ($bsd->getName() === BTN::IRON_DOOR) return count(self::$dataValues[BTN::WOOD]);
                return 0;

            case DoublePlant::class:
                return self::$dataValues[BTN::DOUBLE_PLANT][BlockStateParser::getStateValue($bsd, BSN::DOUBLE_PLANT_TYPE)];

            case Furnace::class:
                return intval($block->isLit());

            case Leaves::class:
                return match ($name) {
                    BTN::LEAVES =>
                    self::$dataValues[BTN::LEAVES][BlockStateParser::getStateValue($bsd, BSN::OLD_LEAF_TYPE)],
                    BTN::LEAVES2 =>
                    self::$dataValues[BTN::LEAVES2][BlockStateParser::getStateValue($bsd, BSN::NEW_LEAF_TYPE)],
                    default => 0
                };

            case RedMushroomBlock::class:
                return $bsd->getState(BSN::HUGE_MUSHROOM_BITS)->getValue();

            case Flower::class:
                return match ($name) {
                    BTN::RED_FLOWER =>
                    self::$dataValues[BTN::RED_FLOWER][BlockStateParser::getStateValue($bsd, BSN::FLOWER_TYPE)],
                    default => 0
                };

            case Sapling::class:
                return self::$dataValues[BTN::SAPLING][BlockStateParser::getStateValue($bsd, BSN::SAPLING_TYPE)];

            case Slab::class:
                return match ($name) {
                    BTN::WOODEN_SLAB, BTN::DOUBLE_WOODEN_SLAB =>
                    self::$dataValues[BTN::WOOD][BlockStateParser::getStateValue($bsd, BSN::WOOD_TYPE)],
                    BTN::STONE_BLOCK_SLAB, BTN::DOUBLE_STONE_BLOCK_SLAB =>
                    self::$dataValues[BTN::STONE_BLOCK_SLAB][BlockStateParser::getStateValue($bsd, BSN::STONE_SLAB_TYPE)],
                    BTN::STONE_BLOCK_SLAB2, BTN::DOUBLE_STONE_BLOCK_SLAB2 =>
                    self::$dataValues[BTN::STONE_BLOCK_SLAB2][BlockStateParser::getStateValue($bsd, BSN::STONE_SLAB_TYPE_2)],
                    BTN::STONE_BLOCK_SLAB3, BTN::DOUBLE_STONE_BLOCK_SLAB3 =>
                    self::$dataValues[BTN::STONE_BLOCK_SLAB3][BlockStateParser::getStateValue($bsd, BSN::STONE_SLAB_TYPE_3)],
                    BTN::STONE_BLOCK_SLAB4, BTN::DOUBLE_STONE_BLOCK_SLAB4 =>
                    self::$dataValues[BTN::STONE_BLOCK_SLAB4][BlockStateParser::getStateValue($bsd, BSN::STONE_SLAB_TYPE_4)],
                    default => 0
                };

            case Wall::class:
                return match ($name) {
                    BTN::COBBLESTONE_WALL =>
                    self::$dataValues[BTN::COBBLESTONE_WALL][BlockStateParser::getStateValue($bsd, BSN::WALL_BLOCK_TYPE)],
                    default => 0
                };

            case Opaque::class:
                return match ($name) {
                    BTN::QUARTZ_BLOCK =>
                    self::$dataValues[BTN::QUARTZ_BLOCK][BlockStateParser::getStateValue($bsd, BSN::CHISEL_TYPE)],
                    BTN::SANDSTONE, BTN::RED_SANDSTONE =>
                    self::$dataValues[BTN::SANDSTONE][BlockStateParser::getStateValue($bsd, BSN::SAND_STONE_TYPE)],
                    BTN::STONE =>
                    self::$dataValues[BTN::STONE][BlockStateParser::getStateValue($bsd, BSN::STONE_TYPE)],
                    BTN::STONEBRICK =>
                    self::$dataValues[BTN::STONEBRICK][BlockStateParser::getStateValue($bsd, BSN::STONE_BRICK_TYPE)],
                    default => 0
                };

            default:
                return 0;
        }
    }
}