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

use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use pocketmine\block\Block;
use pocketmine\block\Candle;
use pocketmine\block\SeaPickle;
use pocketmine\world\format\Chunk;

abstract class GroupModel extends BlockModel
{

    /**
     * A list of all destinations for each group item
     * e.g. for candles and sea pickles:
     * - [[d1], [d1, d2], [d1, d2, d3], [d1, d2, d3, d4]]
     * @return array
     */
    public abstract function getDestLocations(): array;

    /**
     * Get the source position of the top texture
     * @return array
     */
    public abstract function getTopGeometry(): array;

    public function getGeometry(Block $block, Chunk $chunk): array
    {
        $geo = [];

        foreach ($this->getTopGeometry() as $top) {
            $count = 0;
            if (BlockUtils::hasCount($block)) {
                /** @var Candle $block */
                $count = $block->getCount();
            }

            $dest = $this->getDestLocations()[$count-1];
            foreach ($dest as $d) {
                $geo[] = [
                    $top[0],
                    $top[1],
                    $d
                ];
            }
        }

        return $geo;
    }
}