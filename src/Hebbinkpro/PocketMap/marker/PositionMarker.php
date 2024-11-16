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

namespace Hebbinkpro\PocketMap\marker;

use pocketmine\math\Vector3;

abstract class PositionMarker extends BaseMarker
{
    private Vector3 $position;

    public function __construct(?string $id, string $name, Vector3 $position)
    {
        parent::__construct($id, $name);
        $this->position = $position;
    }

    public static function parsePosition(array $data): ?Vector3
    {
        if (!isset($data["x"]) || !isset($data["z"])) return null;
        return new Vector3(floatval($data["x"]), 0, floatval($data["z"]));
    }

    final protected function serializeData(): array
    {
        $data = $this->serializeMarkerData();
        $data["pos"] = [
            "x" => $this->position->getX(),
            "z" => $this->position->getZ(),
        ];

        return $data;
    }

    abstract protected function serializeMarkerData(): array;
}