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

use Hebbinkpro\PocketMap\textures\model\geometry\FlatModelGeometry;
use Hebbinkpro\PocketMap\textures\model\geometry\TexturePosition;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

class FlatCrossModel extends FlatBlockModel
{

    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        return [
            new FlatModelGeometry(
                dstStart: TexturePosition::zero(),
                dstEnd: TexturePosition::max(),
            ),
            new FlatModelGeometry(
                dstStart: TexturePosition::maxY(),
                dstEnd: TexturePosition::maxX(),
            )
        ];
    }
}