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
use pocketmine\world\format\Chunk;

abstract class ConnectionModel extends BlockModel
{
    public function getGeometry(Block $block, Chunk $chunk): array
    {
        $center = $this->getCenterGeometry($block);
        $connections = $this->getConnectionsGeometry($block, $chunk);
        return array_merge($center, $connections);
    }

    /**
     * @param Block $block
     * @return array<array<array{int, int}>>
     */
    public abstract function getCenterGeometry(Block $block): array;

    /**
     * @param Block $block
     * @param Chunk $chunk
     * @return array<array<array{int, int}>>
     */
    public abstract function getConnectionsGeometry(Block $block, Chunk $chunk): array;
}