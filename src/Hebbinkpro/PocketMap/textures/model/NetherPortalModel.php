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

namespace Hebbinkpro\PocketMap\textures\model;

use Hebbinkpro\PocketMap\textures\model\geometry\ModelGeometry;
use Hebbinkpro\PocketMap\textures\model\geometry\TexturePosition;
use pocketmine\block\Block;
use pocketmine\block\NetherPortal;
use pocketmine\math\Axis;
use pocketmine\world\format\Chunk;

class NetherPortalModel extends BlockModel
{

    /**
     * @inheritDoc
     */
    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        if (!$block instanceof NetherPortal) return null;

        $rotation = match ($block->getAxis()) {
            Axis::Z => 90,
            default => 0
        };

        return [
            new ModelGeometry(
                TexturePosition::zero(),
                new TexturePosition(16, 4),
                dstStart: new TexturePosition(0, 6),
                rotation: $rotation
            )
        ];
    }
}