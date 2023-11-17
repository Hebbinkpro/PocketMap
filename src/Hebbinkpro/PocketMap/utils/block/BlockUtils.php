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

    /**
     * @param Block $block
     * @param Chunk $chunk
     * @return array
     */
    public static function getConnections(Block $block, Chunk $chunk): array
    {
        if (!self::hasConnections($block)) return [];

        if ($block instanceof Wall) return array_keys($block->getConnections());

        // TODO: make it work on chunk borders

        $blockPos = $block->getPosition();
        $blockChunk = [
            floor($blockPos->getX() / 16),
            floor($blockPos->getZ() / 16)
        ];

        $connections = [];

        foreach (Facing::HORIZONTAL as $facing) {
            $offset = Facing::OFFSET[$facing];
            $pos = $block->getPosition()->add($offset[0], $offset[1], $offset[2]);
            $posChunk = [
                floor($pos->getX() / 16),
                floor($pos->getZ() / 16)
            ];

            // outside the chunk
            if ($posChunk != $blockChunk) continue;

            $stateId = $chunk->getBlockStateId($pos->x % 16, $pos->y, $pos->z % 16);
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
        return in_array(HorizontalFacingTrait::class, self::getTraits($block));
    }

    public static function hasCount(Block $block): bool {
        return match ($block->getTypeId()) {
            BlockTypeIds::SEA_PICKLE, BlockTypeIds::CANDLE, BlockTypeIds::DYED_CANDLE => true,
            default => false
        };
    }

    /**
     * Get a list of all traits a block (including traits in its parent classes) is using
     * @param Block $block
     * @return string[] the list of all traits
     */
    public static function getTraits(Block $block): array
    {

        try {
            $reflection = new ReflectionClass($block::class);
        } catch (ReflectionException) {
            return [];
        }

        $traits = array_keys($reflection->getTraits());
        foreach (self::getParents($block) as $parent) {
            $traits = array_merge($traits, array_keys($parent->getTraits()));
        }

        return array_unique($traits);
    }

    /**
     * @param Block $block
     * @return ReflectionClass[]
     */
    public static function getParents(Block $block): array
    {
        try {
            $reflection = new ReflectionClass($block::class);
        } catch (ReflectionException) {
            return [];
        }

        $parents = [];
        while (true) {
            if ($reflection->getParentClass() === false) break;
            $parents[] = $reflection->getParentClass();
            $reflection = $reflection->getParentClass();
        }

        return $parents;
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

    public static function hasFullTop(Block $block, float $epsilon = 0.000001): bool
    {

        foreach ($block->getCollisionBoxes() as $cb) {
            if (abs($cb->getXLength() - $cb->getZLength()) < $epsilon) return true;
        }

        return false;
    }
}