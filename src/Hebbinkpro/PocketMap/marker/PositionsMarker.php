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

abstract class PositionsMarker extends BaseMarker
{

    /** @var Vector3[] */
    private array $positions;

    /**
     * @param string|null $id
     * @param string $name
     * @param Vector3[] $positions
     */
    public function __construct(?string $id, string $name, array $positions)
    {
        parent::__construct($id, $name);
        $this->positions = $positions;
    }

    /**
     * Parse a list of positions from the given data
     * @param array $data
     * @return Vector3[]|null
     */
    public static function parsePositions(array $data): ?array
    {
        $positions = [];
        foreach ($data as $posData) {
            $pos = PositionMarker::parsePosition($posData);
            // invalid position
            if ($pos === null) return null;
            $positions[] = $pos;
        }

        return $positions;
    }

    abstract protected function serializeMarkerData(): array;

    protected function serializeData(): array
    {
        $data = $this->serializeMarkerData();
        $positions = array_map(fn($pos) => ["x" => $pos->getX(), "z" => $pos->getZ()], $this->positions);
        $data["positions"] = $positions;

        return $data;
    }
}