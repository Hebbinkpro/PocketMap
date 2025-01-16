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

use Hebbinkpro\PocketMap\textures\model\flat\FlatBlockModel;
use Hebbinkpro\PocketMap\textures\model\geometry\FlatModelGeometry;
use Hebbinkpro\PocketMap\textures\model\geometry\ModelGeometry;
use Hebbinkpro\PocketMap\textures\model\geometry\TexturePosition;
use pocketmine\block\Block;
use pocketmine\block\Chain;
use pocketmine\math\Axis;
use pocketmine\world\format\Chunk;

class ChainModel extends PillarRotationModel
{

    /**
     * @inheritDoc
     */
    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        if (!$block instanceof Chain) return null;

        $axis = $block->getAxis();

        // use offset and use the geo of 3 pixels
        if ($axis === Axis::Y) return FlatBlockModel::cross(6, new FlatModelGeometry(0, 3));

        // I hate chains, there width is 3 pixels
        return [
            new ModelGeometry(
                TexturePosition::zero(),
                new TexturePosition(3, 16),
                new TexturePosition(7, 0),
                new TexturePosition(2, 16)
            )
        ];
    }

}