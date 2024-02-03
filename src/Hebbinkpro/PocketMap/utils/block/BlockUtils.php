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
use pocketmine\block\Chest;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;
use pocketmine\block\Thin;
use pocketmine\block\utils\AnyFacingTrait;
use pocketmine\block\utils\ColoredTrait;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\block\utils\PillarRotationTrait;
use pocketmine\block\utils\SignLikeRotationTrait;
use pocketmine\block\utils\SupportType;
use pocketmine\block\Wall;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;
use ReflectionClass;


class BlockUtils
{
    /**
     * Get if the block is invisible (does not have a texture)
     * @param Block $block
     * @return bool
     */
    public static function isInvisible(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::AIR, BlockTypeIds::BARRIER, BlockTypeIds::INVISIBLE_BEDROCK, BlockTypeIds::LIGHT => true,
            default => false
        };
    }

    /**
     * @param Block $block
     * @param Chunk $chunk
     * @return array<int>
     */
    public static function getConnections(Block $block, Chunk $chunk): array
    {
        if (!self::hasConnections($block)) return [];

        if ($block instanceof Wall) return array_keys($block->getConnections());

        // TODO: make it work on chunk borders

        $blockPos = $block->getPosition();
        $blockChunk = [
            (int)floor($blockPos->getX() / 16),
            (int)floor($blockPos->getZ() / 16)
        ];

        $connections = [];

        foreach (Facing::HORIZONTAL as $facing) {
            $offset = Facing::OFFSET[$facing];
            $pos = $block->getPosition()->add($offset[0], $offset[1], $offset[2]);
            $posChunk = [
                (int)floor($pos->getX() / 16),
                (int)floor($pos->getZ() / 16)
            ];

            // outside the chunk
            if ($posChunk != $blockChunk) continue;

            $stateId = $chunk->getBlockStateId((int)$pos->x % 16, (int)$pos->y, (int)$pos->z % 16);
            $side = BlockStateParser::getBlockFromStateId($stateId);

            if ($block instanceof Fence
                && ($side instanceof Fence || $side instanceof FenceGate || $block->getSupportType(Facing::opposite($facing)) === SupportType::FULL)) {
                $connections[] = $facing;
            } else if ($block instanceof Thin
                && ($side instanceof Thin || $side instanceof Wall || $block->getSupportType(Facing::opposite($facing)) === SupportType::FULL)) {
                $connections[] = $facing;
            }
        }

        return $connections;
    }

    public static function hasConnections(Block $block): bool
    {
        if ($block instanceof Fence || $block instanceof Thin || $block instanceof Wall) return true;

        return false;
    }

    public static function hasDifferentModelForSameState(Block $block): bool
    {
        return $block instanceof Fence || $block instanceof Thin || $block instanceof Chest;
    }

    public static function hasHorizontalFacing(Block $block): bool
    {
        if ($block->getTypeId() == BlockTypeIds::TORCH) return true;
        return in_array(HorizontalFacingTrait::class, self::getTraits($block), true);
    }

    /**
     * Get a list of all traits a block (including traits in its parent classes) is using
     * @param Block $block
     * @return string[] the list of all traits
     */
    public static function getTraits(Block $block): array
    {

        $reflection = new ReflectionClass($block::class);

        $traits = array_keys($reflection->getTraits());
        foreach (self::getParents($block) as $parent) {
            $traits = array_merge($traits, array_keys($parent->getTraits()));
        }

        return array_unique($traits);
    }

    /**
     * @param Block $block
     * @return ReflectionClass<Block>[]
     */
    public static function getParents(Block $block): array
    {
        $reflection = new ReflectionClass($block::class);

        $parents = [];
        while (true) {
            if ($reflection->getParentClass() === false) break;
            $parents[] = $reflection->getParentClass();
            $reflection = $reflection->getParentClass();
        }

        return $parents;
    }

    public static function hasCount(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::SEA_PICKLE, BlockTypeIds::CANDLE, BlockTypeIds::DYED_CANDLE => true,
            default => false
        };
    }

    public static function hasSignLikeRotation(Block $block): bool
    {
        return in_array(SignLikeRotationTrait::class, self::getTraits($block), true);
    }

    public static function hasAnyFacing(Block $block): bool
    {
        if (in_array(AnyFacingTrait::class, self::getTraits($block), true)) return true;

        return match ($block->getTypeId()) {
            BlockTypeIds::LEVER => true,
            default => false
        };
    }

    public static function hasPillarRotation(Block $block): bool
    {
        return in_array(PillarRotationTrait::class, self::getTraits($block), true);
    }

    public static function hasColor(Block $block): bool
    {
        return in_array(ColoredTrait::class, self::getTraits($block), true);
    }

    public static function hasFullTop(Block $block, float $epsilon = 0.000001): bool
    {

        foreach ($block->getCollisionBoxes() as $cb) {
            if (abs($cb->getXLength() - $cb->getZLength()) < $epsilon) return true;
        }

        return false;
    }
}