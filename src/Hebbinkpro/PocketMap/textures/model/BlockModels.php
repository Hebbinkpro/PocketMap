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

namespace Hebbinkpro\PocketMap\textures\model;

use GdImage;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use pocketmine\block\Block;
use pocketmine\block\Cake;
use pocketmine\block\Chest;
use pocketmine\block\Fence;
use pocketmine\block\PressurePlate;
use pocketmine\block\ShulkerBox;
use pocketmine\block\Stair;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wall;
use pocketmine\utils\SingletonTrait;

final class BlockModels
{
    use SingletonTrait;

    private BlockModel $default;
    private array $blockModels;

    public function __construct()
    {
        $this->blockModels = [];
        $this->registerAll();
    }

    private function registerAll(): void {
        $this->default = new DefaultBlockModel();

        $this->register(VanillaBlocks::SEA_LANTERN(), new FullBlockModel());
        //$this->registerFromBlockType(Fence::class, new FenceModel());
        $this->registerFromBlockType(Wall::class, new WallModel());
        $this->registerFromBlockType(Chest::class, new FullBlockModel());
        $this->registerFromBlockType(PressurePlate::class, new FullBlockModel());
        $this->registerFromBlockType(Cake::class, new FullBlockModel());
        $this->registerFromBlockType(Stair::class, new FullBlockModel());
        $this->registerFromBlockType(ShulkerBox::class, new FullBlockModel());
    }

    /**
     * Register a model for all blocks with a given trait
     * @param string $blockTrait
     * @param BlockModel $model
     * @return void
     */
    public function registerFromBlockTrait(string $blockTrait, BlockModel $model): void {
        foreach (VanillaBlocks::getAll() as $block) {
            if (in_array($blockTrait, BlockUtils::getTraits($block))) $this->register($block, $model);
        }
    }

    /**
     * Register a model for all blocks of the same type
     * @param string $blockType parent class name
     * @param BlockModel $model
     * @return void
     */
    public function registerFromBlockType(string $blockType, BlockModel $model): void {
        foreach (VanillaBlocks::getAll() as $block) {
            if ($block instanceof $blockType) $this->register($block, $model);
        }
    }

    /**
     * Register a block model
     * @param Block $block
     * @param BlockModel $model
     * @return void
     */
    public function register(Block $block, BlockModel $model): void {
        $this->blockModels[$block->getTypeId()] = $model;
    }

    public function get(Block $block): ?BlockModel {
        return $this->blockModels[$block->getTypeId()] ?? $this->default;
    }





}