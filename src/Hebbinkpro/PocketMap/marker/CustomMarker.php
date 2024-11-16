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

/**
 * Marker which can be used to implement custom marker types
 */
abstract class CustomMarker extends BaseMarker
{
    public function __construct(?string $id, string $name)
    {
        parent::__construct($id, $name);
    }

    final public static function getMarkerType(): MarkerType
    {
        return MarkerType::CUSTOM;
    }

    /**
     * Parse the given data into this marker
     * @param array $data the data to be parsed
     * @return CustomMarker|null the marker or null if the data was invalid
     */
    abstract public static function parseMarker(array $data): ?self;

    protected function serializeData(): array
    {
        $data = $this->serializeMarkerData();
        $data["custom_type"] = $this->getCustomMarkerType();

        return $data;
    }

    abstract protected function serializeMarkerData(): array;

    abstract public static function getCustomMarkerType(): string;
}