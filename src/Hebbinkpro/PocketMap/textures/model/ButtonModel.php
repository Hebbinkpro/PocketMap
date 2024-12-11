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
use pocketmine\block\Button;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class ButtonModel extends AnyFacingModel
{
    public function getGeometry(Block $block, Chunk $chunk): array
    {
        if (!$block instanceof Button) return parent::getGeometry($block, $chunk);

        $facing = $block->getFacing();
        return match ($facing) {
            Facing::UP, Facing::DOWN => [
                [
                    [5, 0],
                    [6, 2]
                ]
            ],
            default => [
                [
                    [5, 6],
                    [6, 4]
                ]
            ]
        };
    }
}