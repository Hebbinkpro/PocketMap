<?php

namespace Hebbinkpro\PocketMap\utils\block;

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\utils\AnyFacingTrait;
use pocketmine\block\utils\PillarRotationTrait;
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
        } catch (BlockStateSerializeException $e) {
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
        if (in_array(AnyFacingTrait::class, class_uses($block::class))) {
            /** @var AnyFacingTrait $block */

            return $block->getFacing();
        }

        // the block uses the PillarRotationTrait
        if (in_array(PillarRotationTrait::class, class_uses($block::class))) {
            /** @var PillarRotationTrait $block */

            // convert axis to facing
            return ($block->getAxis() << 1) | Facing::FLAG_AXIS_POSITIVE;
        }

        // the block does not have an axis, default is +Y
        return Facing::UP;
    }
}