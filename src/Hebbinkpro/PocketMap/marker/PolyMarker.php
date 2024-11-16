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

use Hebbinkpro\PocketMap\marker\leaflet\LeafletPathOptions;

abstract class PolyMarker extends PositionsMarker
{

    private LeafletPathOptions $options;

    public function __construct(?string $id, string $name, array $positions, ?LeafletPathOptions $options = null)
    {
        parent::__construct($id, $name, $positions);
        $this->options = $options ?? new LeafletPathOptions();
    }

    protected function serializeMarkerData(): array
    {
        return [
            "options" => $this->options->jsonSerialize(),
        ];
    }
}