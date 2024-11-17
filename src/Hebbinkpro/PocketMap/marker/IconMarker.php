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

class IconMarker extends PositionMarker
{
    private string $icon;

    public function __construct(?string $id, string $name, Vector3 $position, string $icon)
    {
        parent::__construct($id, $name, $position);
        $this->icon = $icon;
    }

    public static function getMarkerType(): MarkerType
    {
        return MarkerType::ICON;
    }

    protected function serializeMarkerData(): array
    {
        return [
            "icon" => $this->icon,
        ];
    }
}