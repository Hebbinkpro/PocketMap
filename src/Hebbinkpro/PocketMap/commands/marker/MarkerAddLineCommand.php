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

namespace Hebbinkpro\PocketMap\commands\marker;

use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class MarkerAddLineCommand extends MarkerAddAreaCommand
{

    protected function addMarker(string $name, Vector3 $pos1, Vector3 $pos2, World $world, ?string $id): bool
    {

        // create a rectangular polygon positions list
        $positions = [
            $pos1,
            $pos2
        ];

        return PocketMap::getMarkers()->addPolylineMarker($name, $positions, $world, $id);
    }
}