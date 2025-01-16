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
use pocketmine\world\format\Chunk;

class ChorusFlowerModel extends BlockModel
{

    /**
     * @inheritDoc
     */
    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        $center =
        $side = new ModelGeometry(
            srcStart: new TexturePosition(2, 0),
            srcSize: new TexturePosition(12, 2)
        );

        return [
            ModelGeometry::fromCenter(12),
            new ModelGeometry(
                srcStart: new TexturePosition(2, 0),
                srcSize: new TexturePosition(12, 2)
            ),
            new ModelGeometry(
                srcStart: new TexturePosition(2, 14),
                srcSize: new TexturePosition(12, 2)
            ),
            new ModelGeometry(
                srcStart: new TexturePosition(0, 2),
                srcSize: new TexturePosition(2, 12)
            ),
            new ModelGeometry(
                srcStart: new TexturePosition(14, 2),
                srcSize: new TexturePosition(2, 12)
            )
        ];
    }
}