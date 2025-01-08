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
use pocketmine\block\Block;
use pocketmine\block\PinkPetals;
use pocketmine\world\format\Chunk;

class PinkPetalsModel extends HorizontalFacingModel
{

    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        if (!$block instanceof PinkPetals) return null;

        $count = $block->getCount();

        return match ($count) {
            1 => [
                new ModelGeometry(
                    TexturePosition::zero(),
                    TexturePosition::xy(8)
                )
            ],
            2 => [
                new ModelGeometry(
                    TexturePosition::zero(),
                    new TexturePosition(16, 8)
                )
            ],
            3 => [
                new ModelGeometry(
                    TexturePosition::zero(),
                    new TexturePosition(16, 8)
                ),
                new ModelGeometry(
                    TexturePosition::xy(8),
                    TexturePosition::xy(8)
                )
            ],
            default => self::getDefaultGeometry()
        };
    }
}