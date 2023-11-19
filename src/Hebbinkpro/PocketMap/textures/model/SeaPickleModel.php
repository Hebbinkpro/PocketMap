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
 * Copyright (C) 2023 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\textures\model;

class SeaPickleModel extends GroupModel
{

    /**
     * @inheritDoc
     */
    public function getDestLocations(): array
    {
        return [
            [
                [6, 6]
            ],
            [
                [4, 4],
                [8, 8]
            ],
            [
                [3, 3],
                [8, 4],
                [6, 9]
            ],
            [
                [3, 3],
                [9, 2],
                [2, 9],
                [9, 10]
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTopGeometry(): array
    {
        return [
            // bottom part of the top texture
            [
                [8, 1],
                [4, 4],
            ],
            // top part of the top texture
            [
                [4, 1],
                [4, 4]
            ]
        ];
    }
}