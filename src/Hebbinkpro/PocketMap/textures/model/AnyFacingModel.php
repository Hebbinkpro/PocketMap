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
use pocketmine\block\Block;
use pocketmine\block\Button;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

abstract class AnyFacingModel extends BlockModel
{

    public function getGeometry(Block $block, Chunk $chunk): array
    {
        /** @var Button $block */
        if (BlockUtils::hasAnyFacing($block)) {
            $facing = $block->getFacing();
            if (in_array($block->getFacing(), $this->getSideFacing(), true)) {
                return $this->getSideGeometry($facing);
            }

            return $this->getTopGeometry($facing);
        }

        return $this->getTopGeometry(Facing::UP);
    }

    /**
     * @return array{int, int}
     */
    public function getSideFacing(): array
    {
        return [
            Facing::UP,
            Facing::DOWN
        ];
    }

    /**
     * @param int $facing
     * @return int[][][]
     */
    public abstract function getSideGeometry(int $facing): array;

    /**
     * @param int $facing
     * @return int[][][]
     */
    public abstract function getTopGeometry(int $facing): array;

}