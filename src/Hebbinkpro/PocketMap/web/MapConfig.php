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

namespace Hebbinkpro\PocketMap\web;

use JsonSerializable;

class MapConfig implements JsonSerializable
{
    private string $defaultWorld;
    private MapSettings $defaultSettings;
    /** @var array<string, MapSettings> */
    private array $worldSettings;

    /**
     * @param string $defaultWorld
     * @param MapSettings $defaultSettings
     * @param MapSettings[] $worldSettings
     */
    public function __construct(string $defaultWorld, MapSettings $defaultSettings, array $worldSettings)
    {
        $this->defaultWorld = $defaultWorld;
        $this->defaultSettings = $defaultSettings;
        $this->worldSettings = $worldSettings;
    }

    /**
     * @return string
     */
    public function getDefaultWorld(): string
    {
        return $this->defaultWorld;
    }

    /**
     * @return MapSettings
     */
    public function getDefaultSettings(): MapSettings
    {
        return $this->defaultSettings;
    }

    /**
     * @return array
     */
    public function getWorldSettings(): array
    {
        return $this->worldSettings;
    }

    public function jsonSerialize(): array
    {
        return [
            "default-world" => $this->defaultWorld,
            "default-settings" => $this->defaultSettings->jsonSerialize(),
            "worlds" => array_map(fn($setting) => $setting->jsonSerialize(), $this->worldSettings),
        ];
    }
}