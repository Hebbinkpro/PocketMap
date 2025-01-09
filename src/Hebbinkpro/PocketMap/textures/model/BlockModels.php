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

use Hebbinkpro\PocketMap\textures\model\flat\BrewingStandModel;
use Hebbinkpro\PocketMap\textures\model\flat\CropsModel;
use Hebbinkpro\PocketMap\textures\model\flat\FlatCrossModel;
use Hebbinkpro\PocketMap\textures\model\flat\FlatHorizontalFacingModel;
use Hebbinkpro\PocketMap\textures\model\flat\ItemFrameModel;
use Hebbinkpro\PocketMap\textures\model\flat\MultiAnyFacingModel;
use Hebbinkpro\PocketMap\textures\model\flat\StemModel;
use Hebbinkpro\PocketMap\textures\model\flat\VinesModel;
use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use pocketmine\block\BaseRail;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Button;
use pocketmine\block\Campfire;
use pocketmine\block\Coral;
use pocketmine\block\Crops;
use pocketmine\block\Door;
use pocketmine\block\DoublePlant;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;
use pocketmine\block\FloorBanner;
use pocketmine\block\FloorSign;
use pocketmine\block\Flowable;
use pocketmine\block\Flower;
use pocketmine\block\ItemFrame;
use pocketmine\block\PressurePlate;
use pocketmine\block\Sapling;
use pocketmine\block\Stair;
use pocketmine\block\Stem;
use pocketmine\block\Thin;
use pocketmine\block\Torch;
use pocketmine\block\Trapdoor;
use pocketmine\block\utils\AnyFacingTrait;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\block\utils\MultiAnyFacingTrait;
use pocketmine\block\utils\MultiAnySupportTrait;
use pocketmine\block\utils\PillarRotationTrait;
use pocketmine\block\utils\SignLikeRotationTrait;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wall;
use pocketmine\block\WallBanner;
use pocketmine\block\WallSign;
use pocketmine\utils\SingletonTrait;

final class BlockModels
{
    use SingletonTrait;

    private BlockModel $default;
    /** @var array<int, BlockModelInterface> */
    private array $blockModels;

    /** @var array<class-string<Block>, BlockModelInterface> */
    private array $blockClassModels;

    /** @var array<trait-string, BlockModelInterface> */
    private array $blockTraitModels;

    public function __construct()
    {
        $this->blockModels = [];
        $this->blockClassModels = [];
        $this->blockTraitModels = [];
        $this->default = new FullBlockModel();

        $this->registerAll();
    }

    /**
     * Register all default models in the order blocks->blockTypes->blockTraits
     * @return void
     */
    private function registerAll(): void
    {
        // register blocks
        $this->register(VanillaBlocks::WATER(), new FullBlockModel());
        $this->register(VanillaBlocks::LAVA(), new FullBlockModel());
        $this->register(VanillaBlocks::BIG_DRIPLEAF_STEM(), new FlatCrossModel());
        $this->register(VanillaBlocks::BREWING_STAND(), new FlatCrossModel());
        $this->register(VanillaBlocks::AMETHYST_CLUSTER(), new FlatCrossModel());
        $this->register(VanillaBlocks::PITCHER_CROP(), new FullBlockModel());
        $this->register(VanillaBlocks::END_ROD(), new EndRodModel());
        $this->register(VanillaBlocks::CHEST(), new FullBlockModel()); // TODO double chests
        $this->register(VanillaBlocks::TRAPPED_CHEST(), new FullBlockModel());
        $this->register(VanillaBlocks::ENDER_CHEST(), new FullBlockModel());
        $this->register(VanillaBlocks::CAKE(), new FullBlockModel());
        $this->register(VanillaBlocks::CAKE_WITH_CANDLE(), new FullBlockModel());
        $this->register(VanillaBlocks::CAKE_WITH_DYED_CANDLE(), new FullBlockModel());
        $this->register(VanillaBlocks::PINK_PETALS(), new FullBlockModel());
        $this->register(VanillaBlocks::CANDLE(), new CandleModel());
        $this->register(VanillaBlocks::DYED_CANDLE(), new CandleModel());
        $this->register(VanillaBlocks::SEA_PICKLE(), new SeaPickleModel());
        $this->register(VanillaBlocks::CHORUS_PLANT(), new WallModel());
        $this->register(VanillaBlocks::CHORUS_FLOWER(), new FullBlockModel());
        $this->register(VanillaBlocks::LIGHTNING_ROD(), new LightningRodModel());
        $this->register(VanillaBlocks::LEVER(), new LeverModel());
        $this->register(VanillaBlocks::VINES(), new VinesModel());
        $this->register(VanillaBlocks::PINK_PETALS(), new PinkPetalsModel());
        $this->register(VanillaBlocks::CARPET(), new FullBlockModel());
        $this->register(VanillaBlocks::LILY_PAD(), new FullBlockModel());
        $this->register(VanillaBlocks::REDSTONE_WIRE(), new FullBlockModel());
        $this->register(VanillaBlocks::REDSTONE(), new FullBlockModel());
        $this->register(VanillaBlocks::TRIPWIRE(), new FullBlockModel());
        $this->register(VanillaBlocks::MOB_HEAD(), new FullBlockModel());
        $this->register(VanillaBlocks::LADDER(), new FlatHorizontalFacingModel());
        $this->register(VanillaBlocks::SNOW_LAYER(), new FullBlockModel());
        $this->register(VanillaBlocks::BAMBOO(), new BambooModel());
        $this->register(VanillaBlocks::BELL(), new BellModel());
        $this->register(VanillaBlocks::BREWING_STAND(), new BrewingStandModel());
        $this->register(VanillaBlocks::CAKE(), new CakeModel());
        $this->register(VanillaBlocks::COCOA_POD(), new CocoaModel());
        $this->register(VanillaBlocks::DRAGON_EGG(), new CenteredBlockModel(14));
        $this->register(VanillaBlocks::NETHER_WART(), new CropsModel());
        $this->register(VanillaBlocks::SWEET_BERRY_BUSH(), new FlatCrossModel());
        // TODO (Soul) Fire
        // TODO (Soul) Lantern
        // TODO Lectern
        // TODO Nether portal
        // TODO Redstone
        // TODO Sea Lantern
        // TODO Mob Head
        // TODO Tripwire
        // TODO Tripwire Hook
        // TODO Floor Coral Fan
        // TODO Sculk
        // TODO Chain
        // TODO Small Dripleaf
        // TODO Big Dripleaf
        // TODO Spore Blossom
        // TODO Chorus Flower

        // register block types
        $this->registerClass(Fence::class, new FenceModel());
        $this->registerClass(Wall::class, new WallModel());
        $this->registerClass(Thin::class, new ThinConnectionModel());
        $this->registerClass(FenceGate::class, new FenceGateModel());
        $this->registerClass(Door::class, new DoorModel());
        $this->registerClass(DoublePlant::class, new FlatCrossModel());
        $this->registerClass(Sapling::class, new FlatCrossModel());
        $this->registerClass(Flower::class, new FlatCrossModel());
        $this->registerClass(PressurePlate::class, new CenteredBlockModel(14));
        $this->registerClass(Button::class, new ButtonModel());
        $this->registerClass(Torch::class, new TorchModel());
        $this->registerClass(Stair::class, new FullBlockModel());
        $this->registerClass(BaseRail::class, new RailModel());
        $this->registerClass(ItemFrame::class, new ItemFrameModel());
        $this->registerClass(Trapdoor::class, new TrapdoorModel());
        $this->registerClass(WallSign::class, new WallSignModel());
        $this->registerClass(FloorSign::class, new FloorSignModel());
        $this->registerClass(WallBanner::class, new WallSignModel());   // TODO discover the real banner textures...
        $this->registerClass(FloorBanner::class, new FloorSignModel());
        $this->registerClass(Coral::class, new FlatCrossModel());
        $this->registerClass(Campfire::class, new CampfireModel());
        $this->registerClass(Stem::class, new StemModel()); // should be defined before Crops
        $this->registerClass(Crops::class, new CropsModel());

        // register traits
        $this->registerTrait(HorizontalFacingTrait::class, new HorizontalFacingModel());
        $this->registerTrait(FacesOppositePlacingPlayerTrait::class, new HorizontalFacingModel());
        $this->registerTrait(PillarRotationTrait::class, new PillarRotationModel());
        $this->registerTrait(AnyFacingTrait::class, new AnyFacingModel());
        $this->registerTrait(MultiAnyFacingTrait::class, new MultiAnyFacingModel());
        $this->registerTrait(MultiAnySupportTrait::class, new MultiAnyFacingModel());
        $this->registerTrait(SignLikeRotationTrait::class, new SignLikeRotationModel());
    }

    /**
     * Register a block model
     * @param Block $block
     * @param BlockModelInterface $model
     * @return void
     */
    public function register(Block $block, BlockModelInterface $model): void
    {
        $this->blockModels[$block->getTypeId()] = $model;
    }

    /**
     * Register a model for all blocks of the same (parent) class
     * @param class-string<Block> $class class name
     * @param BlockModelInterface $model
     * @return void
     */
    public function registerClass(string $class, BlockModelInterface $model): void
    {
        $this->blockClassModels[$class] = $model;
    }

    /**
     * Register a model for all blocks with the given trait
     * @param trait-string $trait
     * @param BlockModelInterface $model
     * @return void
     */
    public function registerTrait(string $trait, BlockModelInterface $model): void
    {
        $this->blockTraitModels[$trait] = $model;
    }

    public function get(Block $block): ?BlockModelInterface
    {
        // ignore some invisible blocks
        if (in_array($block->getTypeId(), [BlockTypeIds::AIR, BlockTypeIds::BARRIER])) return null;

        $model = $this->blockModels[$block->getTypeId()] ?? $this->getByClass($block) ?? $this->getByTrait($block);
        if ($model !== null) return $model;

        if ($block instanceof Flowable) return new FlatCrossModel();

        return new FullBlockModel();
    }

    public function getByClass(Block $block): ?BlockModelInterface
    {
        $model = $this->blockClassModels[$block::class] ?? null;
        if ($model !== null) return $model;

        $parents = BlockUtils::getParents($block);

        // test the parents
        foreach ($parents as $parent) {
            $model = $this->blockClassModels[$parent->getName()] ?? null;
            if ($model !== null) return $model;
        }

        return null;
    }

    public function getByTrait(Block $block): ?BlockModelInterface
    {
        $traits = BlockUtils::getTraits($block);

        foreach ($traits as $trait) {
            // Return the first matching trait. If this is not desired, register the block.
            $model = $this->blockTraitModels[$trait] ?? null;
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