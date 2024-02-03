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

class CandleModel extends GroupModel
{

    /**
     * @inheritDoc
     */
    public function getDestLocations(): array
    {
        return [
            [
                [7, 7]
            ],
            [
                [5, 8],
                [9, 7]
            ],
            [
                [5, 7],
                [7, 9],
                [8, 6]
            ],
            [
                [5, 5],
                [8, 5],
                [6, 8],
                [9, 8]
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTopGeometry(): array
    {
        return [
            [
                [0, 6],
                [2, 2]
            ]
        ];
    }
}