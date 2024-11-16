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

use JsonSerializable;

abstract class BaseMarker implements JsonSerializable
{
    private string $id;
    private string $name;

    public function __construct(?string $id, string $name)
    {
        // use the given string ID, or get a new marker ID from the marker manager
        $this->id = $id ?? MarkerManager::getMarkerId();
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    final public function jsonSerialize(): array
    {
        // get the serialized data and append the marker type
        $data = $this->serializeData();
        $data["type"] = $this->getMarkerType()->value;

        // return the name and data. id should be used as index, not as array value
        return [
            "name" => $this->name,
            "data" => $data
        ];
    }

    abstract protected function serializeData(): array;

    abstract public static function getMarkerType(): MarkerType;
}