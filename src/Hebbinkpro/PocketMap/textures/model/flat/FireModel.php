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

use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

class FireModel extends FlatBlockModel
{

    /**
     * @inheritDoc
     */
    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        // TODO fire model is different for the surrounding blocks
        //      but this is only a client side thing.
        // TODO fire is more of a 3d model in-game, but this flat model will suffice
        return [
            // add a cross
            ...FlatBlockModel::cross(),
            // add a square
            ...FlatBlockModel::square(),
        ];
    }
}