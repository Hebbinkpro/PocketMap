<?php

namespace Hebbinkpro\PocketMap\utils\block;

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\convert\BlockObjectToStateSerializer;

final class BlockStateParser
{

    /**
     * Get the BlockStateData of a given block
     * @param Block $block the block to get the BlockStateDate from
     * @return BlockStateData the block state data of the block
     */
    public static function getBlockStateData(Block $block): BlockStateData
    {
        $serializer = new BlockObjectToStateSerializer();
        return $serializer->serializeBlock($block);
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
}