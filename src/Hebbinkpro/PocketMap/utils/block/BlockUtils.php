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

use Generator;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Chest;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;
use pocketmine\block\Thin;
use pocketmine\block\Torch;
use pocketmine\block\utils\AnyFacingTrait;
use pocketmine\block\utils\ColoredTrait;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\block\utils\PillarRotationTrait;
use pocketmine\block\utils\PoweredByRedstoneTrait;
use pocketmine\block\utils\RailPoweredByRedstoneTrait;
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
        if ($block instanceof Torch) return true;
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
     * Iterates through all parents and yields a parent when found.
     * @param Block $block
     * @return Generator<ReflectionClass<Block>>
     */
    public static function getParents(Block $block): Generator
    {
        $reflection = new ReflectionClass($block::class);

        while (($parent = $reflection->getParentClass()) !== false) {
            yield $parent;

            $reflection = $parent;
        }
    }

    /**
     * Check if the block can have multiple items on the same block
     * @param Block $block
     * @return bool
     */
    public static function hasCount(Block $block): bool
    {
        return match ($block->getTypeId()) {
            BlockTypeIds::SEA_PICKLE, BlockTypeIds::CANDLE, BlockTypeIds::DYED_CANDLE => true,
            default => false
        };
    }

    /**
     * Check if the block has sign rotation
     * @param Block $block
     * @return bool
     */
    public static function hasSignLikeRotation(Block $block): bool
    {
        return self::hasTrait($block, SignLikeRotationTrait::class);
    }

    /**
     * Get if the block has the given trait
     * @param Block $block
     * @param trait-string $trait
     * @return bool
     */
    public static function hasTrait(Block $block, string $trait): bool
    {
        $reflection = new ReflectionClass($block::class);

        // check self
        $traits = array_keys($reflection->getTraits());
        if (in_array($trait, $traits, true)) return true;

        // check parents
        /** @var ReflectionClass<Block> $parent */
        foreach (self::getParents($block) as $parent) {
            $traits = array_keys($parent->getTraits());
            if (in_array($trait, $traits, true)) return true;
        }

        return false;
    }

    /**
     * Check if a block can face in any direction
     * @param Block $block
     * @return bool
     */
    public static function hasAnyFacing(Block $block): bool
    {
        if ($block->getTypeId() == BlockTypeIds::LEVER) return true;
        return self::hasTrait($block, AnyFacingTrait::class);
    }

    /**
     * Check if a block has pillar rotation
     * @param Block $block
     * @return bool
     */
    public static function hasPillarRotation(Block $block): bool
    {
        return self::hasTrait($block, PillarRotationTrait::class);
    }

    /**
     * Chec if the block can be colored
     * @param Block $block
     * @return bool
     */
    public static function hasColor(Block $block): bool
    {
        return self::hasTrait($block, ColoredTrait::class);
    }

    /**
     * Check if the top side of the block occupies the whole block
     * @param Block $block
     * @param float $epsilon the precision
     * @return bool
     */
    public static function hasFullTop(Block $block, float $epsilon = 0.000001): bool
    {

        foreach ($block->getCollisionBoxes() as $cb) {
            if (abs($cb->getXLength() - $cb->getZLength()) < $epsilon) return true;
        }

        return false;
    }

    /**
     * Check if redstone can activate the block
     * @param Block $block
     * @return bool
     */
    public static function isPoweredByRedstone(Block $block): bool
    {
        return self::hasTrait($block, PoweredByRedstoneTrait::class) || self::hasTrait($block, RailPoweredByRedstoneTrait::class);
    }
}