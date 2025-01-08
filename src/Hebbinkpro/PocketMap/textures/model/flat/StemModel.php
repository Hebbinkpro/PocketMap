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
 * Copyright (c) 2025 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\textures\model\flat;

use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\textures\model\geometry\FlatModelGeometry;
use Hebbinkpro\PocketMap\textures\model\geometry\TexturePosition;
use pocketmine\block\Block;
use pocketmine\block\Stem;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class StemModel extends FlatCrossModel
{

    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        if (!$block instanceof Stem) return null;

        if ($block->getFacing() != Facing::UP) {
            // default facing is west

            $rotation = match ($block->getFacing()) {
                Facing::NORTH => 90,
                Facing::EAST => 180,
                Facing::SOUTH => 270,
                default => 0
            };

            return [
                new FlatModelGeometry(
                    dstStart: new TexturePosition(0, 7),
                    dstEnd: new TexturePosition(15, 7),
                    rotation: $rotation
                )
            ];
        }

        // cross model
        return parent::getGeometry($block, $chunk);
    }

    protected function getMaxHeight(Block $block, Chunk $chunk): int
    {
        if (!$block instanceof Stem) return parent::getMaxHeight($block, $chunk);

        if ($block->getFacing() != Facing::UP) return PocketMap::TEXTURE_SIZE;

        $age = $block->getAge();
        if ($age == 0) return 1;

        return 4 + ($age - 1) * 2;
    }
}