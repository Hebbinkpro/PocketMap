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

use pocketmine\block\Block;
use pocketmine\block\Lever;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

/**
 * TODO the lever uses the lever_particle/cobblestone texture for its base
 */
class LeverModel extends RotatedBlockModel
{
    public function getGeometry(Block $block, Chunk $chunk): array
    {
        if (!$block instanceof Lever) return parent::getGeometry($block, $chunk);

        $facing = $block->getFacing()->getFacing();
        $activated = $block->isActivated();

        if (in_array($facing, [Facing::UP, Facing::DOWN])) {
            $rotation = $activated ? 180 : 0;
            return [
                [
                    [7, 6],
                    [2, 9],       // ignore the last row of pixels, they are hidden in the base
                    [7, 0],
                    [2, 7],
                    $rotation    // rotate by 180 degrees when activated
                ]
            ];
        }

        $size = $activated ? [2, 9] : [2, 7];
        return [
            [
                [7, 6],
                $size,
                [7, 6],
                [2, 7],
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRotation(Block $block): int
    {
        if (!$block instanceof Lever) return 0;

        return match ($block->getFacing()->getFacing()) {
            Facing::DOWN, Facing::UP, Facing::NORTH => 0,
            Facing::EAST => 270,
            Facing::SOUTH => 180,
            Facing::WEST => 90,
        };
    }
}