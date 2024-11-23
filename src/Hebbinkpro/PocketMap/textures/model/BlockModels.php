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

use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use pocketmine\block\BaseRail;
use pocketmine\block\Block;
use pocketmine\block\Button;
use pocketmine\block\Crops;
use pocketmine\block\Door;
use pocketmine\block\DoublePlant;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;
use pocketmine\block\Flower;
use pocketmine\block\PressurePlate;
use pocketmine\block\Sapling;
use pocketmine\block\Stair;
use pocketmine\block\Thin;
use pocketmine\block\Torch;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wall;
use pocketmine\utils\SingletonTrait;

final class BlockModels
{
    use SingletonTrait;

    private BlockModel $default;
    /** @var array<int, BlockModel> */
    private array $blockModels;

    /** @var array<string, BlockModel> */
    private array $blockTypeModels;

    public function __construct()
    {
        $this->blockModels = [];
        $this->blockTypeModels = [];

        $this->registerAll();
    }

    private function registerAll(): void
    {
        $this->default = new DefaultBlockModel();

        $this->register(VanillaBlocks::WATER(), new DefaultBlockModel());
        $this->register(VanillaBlocks::LAVA(), new DefaultBlockModel());

        $this->registerFromBlockType(Fence::class, new FenceModel());
        $this->registerFromBlockType(Wall::class, new WallModel());
        $this->registerFromBlockType(Thin::class, new ThinConnectionModel());
        $this->registerFromBlockType(FenceGate::class, new FenceGateModel());
        $this->registerFromBlockType(Door::class, new DoorModel());

        $this->register(VanillaBlocks::TALL_GRASS(), new CrossModel());
        $this->register(VanillaBlocks::FERN(), new CrossModel());
        $this->register(VanillaBlocks::DOUBLE_TALLGRASS(), new CrossModel());
        $this->register(VanillaBlocks::LARGE_FERN(), new CrossModel());
        $this->registerFromBlockType(DoublePlant::class, new CrossModel());
        $this->registerFromBlockType(Sapling::class, new CrossModel());
        $this->registerFromBlockType(Crops::class, new CrossModel());
        $this->registerFromBlockType(Flower::class, new CrossModel());

        $this->register(VanillaBlocks::CORAL(), new CrossModel());
        $this->register(VanillaBlocks::RED_MUSHROOM(), new CrossModel());
        $this->register(VanillaBlocks::BROWN_MUSHROOM(), new CrossModel());
        $this->register(VanillaBlocks::TWISTING_VINES(), new CrossModel());
        $this->register(VanillaBlocks::WEEPING_VINES(), new CrossModel());
        $this->register(VanillaBlocks::CRIMSON_ROOTS(), new CrossModel());
        $this->register(VanillaBlocks::WARPED_ROOTS(), new CrossModel());
        $this->register(VanillaBlocks::CAVE_VINES(), new CrossModel());
        $this->register(VanillaBlocks::FIRE(), new CrossModel());
        $this->register(VanillaBlocks::SOUL_FIRE(), new CrossModel());
        $this->register(VanillaBlocks::CHAIN(), new CrossModel());
        $this->register(VanillaBlocks::BIG_DRIPLEAF_STEM(), new CrossModel());
        $this->register(VanillaBlocks::BREWING_STAND(), new CrossModel());
        $this->register(VanillaBlocks::COBWEB(), new CrossModel());
        $this->register(VanillaBlocks::SWEET_BERRY_BUSH(), new CrossModel());
        $this->register(VanillaBlocks::DEAD_BUSH(), new CrossModel());
        $this->register(VanillaBlocks::HANGING_ROOTS(), new CrossModel());
        $this->register(VanillaBlocks::AMETHYST_CLUSTER(), new CrossModel());

        $this->registerFromBlockType(PressurePlate::class, new PressurePlateModel());
        $this->registerFromBlockType(Button::class, new ButtonModel());

        $this->register(VanillaBlocks::PITCHER_CROP(), new DefaultBlockModel());
        $this->register(VanillaBlocks::DOUBLE_PITCHER_CROP(), new CrossModel());
        $this->register(VanillaBlocks::TORCHFLOWER_CROP(), new CrossModel());

        $this->register(VanillaBlocks::END_ROD(), new EndRodModel());

        $this->register(VanillaBlocks::CHEST(), new DefaultBlockModel()); // TODO double chests
        $this->register(VanillaBlocks::TRAPPED_CHEST(), new DefaultBlockModel());
        $this->register(VanillaBlocks::ENDER_CHEST(), new DefaultBlockModel());
        $this->register(VanillaBlocks::BARREL(), new DefaultBlockModel());

        $this->register(VanillaBlocks::CAKE(), new DefaultBlockModel());
        $this->register(VanillaBlocks::CAKE_WITH_CANDLE(), new DefaultBlockModel());
        $this->register(VanillaBlocks::CAKE_WITH_DYED_CANDLE(), new DefaultBlockModel());

        $this->register(VanillaBlocks::PINK_PETALS(), new DefaultBlockModel());

        $this->registerFromBlockType(Torch::class, new TorchModel());

        $this->register(VanillaBlocks::CANDLE(), new CandleModel());
        $this->register(VanillaBlocks::DYED_CANDLE(), new CandleModel());
        $this->register(VanillaBlocks::SEA_PICKLE(), new SeaPickleModel());

        $this->register(VanillaBlocks::CHORUS_PLANT(), new WallModel()); // it almost looks like the wall model
        $this->register(VanillaBlocks::CHORUS_FLOWER(), new DefaultBlockModel());

        $this->registerFromBlockType(Stair::class, new DefaultBlockModel()); // TODO height difference

        $this->registerFromBlockType(BaseRail::class, new RailModel());

    }

    /**
     * Register a block model
     * @param Block $block
     * @param BlockModel $model
     * @return void
     */
    public function register(Block $block, BlockModel $model): void
    {
        $this->blockModels[$block->getTypeId()] = $model;
    }

    /**
     * Register a model for all blocks of the same type
     * @param class-string<Block> $blockType parent class name
     * @param BlockModel $model
     * @return void
     */
    private function registerFromBlockType(string $blockType, BlockModel $model): void
    {
        $this->blockTypeModels[$blockType] = $model;
    }

    public function get(Block $block): ?BlockModel
    {
        $model = $this->blockModels[$block->getTypeId()] ?? $this->getByType($block) ?? null;
        if ($model !== null) return $model;

        if ($block->isFullCube() || BlockUtils::hasFullTop($block)) return $this->default;

        return null;
    }

    public function getByType(Block $block): ?BlockModel
    {
        $model = $this->blockTypeModels[$block::class] ?? null;
        if ($model !== null) return $model;

        $parents = BlockUtils::getParents($block);

        // test the parents
        foreach ($parents as $parent) {
            $model = $this->blockTypeModels[$parent->getName()] ?? null;
            if ($model !== null) return $model;
        }

        return null;
    }


    /**
     * @return BlockModel
     */
    public function getDefault(): BlockModel
    {
        return $this->default;
    }
}