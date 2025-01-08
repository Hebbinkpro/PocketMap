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
 * Copyright (c) 2024-2025 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\textures\model;

use Hebbinkpro\PocketMap\textures\model\geometry\ModelGeometry;
use Hebbinkpro\PocketMap\textures\model\geometry\TexturePosition;
use pocketmine\block\Bamboo;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

/**
 * TODO leaves
 */
class BambooModel extends BlockModel
{

    /**
     * @inheritDoc
     */
    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        if (!$block instanceof Bamboo) return null;

        $size = $block->isThick() ? 3 : 2;

        $modelOffset = $block->getModelPositionOffset();
        $offset = new TexturePosition(floor($modelOffset->getX() * 16), floor($modelOffset->getZ() * 16));

        return [
            new ModelGeometry(
                new TexturePosition(13, 0),
                new TexturePosition($size, $size),
                $offset
            )
        ];
    }
}