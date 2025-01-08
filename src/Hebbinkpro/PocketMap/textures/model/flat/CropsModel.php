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

namespace Hebbinkpro\PocketMap\textures\model\flat;

use Hebbinkpro\PocketMap\textures\model\geometry\FlatModelGeometry;
use Hebbinkpro\PocketMap\textures\model\geometry\TexturePosition;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

class CropsModel extends FlatBlockModel
{

    /**
     * @inheritDoc
     */
    public function getGeometry(Block $block, Chunk $chunk): array
    {
        return [
            new FlatModelGeometry(
                dstStart: new TexturePosition(0, 4),
                dstEnd: new TexturePosition(16, 4),
            ),
            new FlatModelGeometry(
                dstStart: new TexturePosition(0, 4),
                dstEnd: new TexturePosition(16, 4),
                rotation: 90
            ),
            new FlatModelGeometry(
                dstStart: new TexturePosition(0, 4),
                dstEnd: new TexturePosition(16, 4),
                rotation: 180
            ),
            new FlatModelGeometry(
                dstStart: new TexturePosition(0, 4),
                dstEnd: new TexturePosition(16, 4),
                rotation: 270
            )
        ];
    }
}