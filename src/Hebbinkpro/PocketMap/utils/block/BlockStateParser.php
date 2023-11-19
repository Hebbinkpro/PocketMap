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

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Button;
use pocketmine\block\Door;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\SimplePillar;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateSerializeException;
use pocketmine\math\Facing;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

final class BlockStateParser
{

    /**
     * Get the BlockStateData of a given block
     * @param Block $block the block to get the BlockStateDate from
     * @return BlockStateData|null the block state data of the block
     */
    public static function getBlockStateData(Block $block): ?BlockStateData
    {
        try {
            return GlobalBlockStateHandlers::getSerializer()->serializeBlock($block);
        } catch (BlockStateSerializeException) {
            // catch serialize exception, this only occurs when the block does not exist in the serializer
            return null;
        }
    }

    /**
     * Get the state value of a given BlockStateData
     * @param BlockStateData $blockStateData the block state data to read the state from
     * @param string $state the state to read
     * @return mixed the value of the state or null when it doesn't exist
     */
    public static function getStateValue(BlockStateData $blockStateData, string $state): mixed
    {
        return $blockStateData->getState($state)?->getValue();
    }

    /**
     * Get a block from its state id
     * @param int $stateId the state id of the block
     * @return Block the block
     */
    public static function getBlockFromStateId(int $stateId): Block
    {
        return RuntimeBlockStateRegistry::getInstance()->fromStateId($stateId);
    }

    /**
     * Get the face of a block
     * @param Block $block
     * @return int
     */
    public static function getBlockFace(Block $block): int
    {

        // the block uses the AnyFacingTrait
        if (BlockUtils::hasAnyFacing($block)) {
            /** @var Button $block */

            return $block->getFacing();
        }

        // the block uses the PillarRotationTrait
        if (BlockUtils::hasPillarRotation($block)) {
            /** @var SimplePillar $block */

            // convert axis to facing
            return ($block->getAxis() << 1) | Facing::FLAG_AXIS_POSITIVE;
        }

        // east is the upper door part
        if ($block instanceof Door) return Facing::EAST;

        // the block does not have an axis, default is +Y
        return match ($block->getTypeId()) {
            // top textures is made of the east texture
            BlockTypeIds::GLASS_PANE, BlockTypeIds::STAINED_GLASS_PANE,
            BlockTypeIds::HARDENED_GLASS_PANE, BlockTypeIds::STAINED_HARDENED_GLASS_PANE => Facing::EAST,
            default => Facing::UP
        };
    }
}