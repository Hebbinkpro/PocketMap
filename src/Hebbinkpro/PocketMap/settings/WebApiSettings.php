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

namespace Hebbinkpro\PocketMap\settings;

use Hebbinkpro\PocketMap\web\MapConfig;
use Hebbinkpro\PocketMap\web\MapSettings;
use pocketmine\utils\Config;

class WebApiSettings extends ConfigSettings
{
    private int $period;
    private array $worlds;
    private MapConfig $mapConfig;
    private bool $playersVisible;
    private bool $showY;
    /** @var string[] */
    private array $playerHideWorlds;
    /** @var string[] */
    private array $hiddenPlayers;

    /**
     * @param int $period
     * @param array $worlds
     * @param MapConfig $mapConfig
     * @param bool $playersVisible
     * @param bool $showY
     * @param string[] $playerHideWorlds
     * @param string[] $hiddenPlayers
     */
    public function __construct(int $period, array $worlds, MapConfig $mapConfig, bool $playersVisible, bool $showY, array $playerHideWorlds, array $hiddenPlayers)
    {
        $this->period = $period;
        $this->worlds = $worlds;
        $this->mapConfig = $mapConfig;
        $this->playersVisible = $playersVisible;
        $this->showY = $showY;
        $this->playerHideWorlds = $playerHideWorlds;
        $this->hiddenPlayers = $hiddenPlayers;
    }

    public static function fromConfig(Config $config): self
    {
        $api = $config->get("api");
        $period = intval($api["update-period"] ?? 20);
        $worlds = $api["worlds"];

        $defaultWorld = strval($api["default-world"] ?? "");
        $mapSettings = MapSettings::fromArray($api["map-settings"]);
        $worldSettings = array_map(fn($data) => MapSettings::fromArray($data), $api["world-settings"]);
        $mapConfig = new MapConfig($defaultWorld, $mapSettings, $worldSettings);

        $players = $api["players"] ?? [];
        $playersVisible = boolval($players["visible"] ?? false);
        $showY = intval($players["show-y"] ?? 0);
        $playerHideWorlds = $players["hide-worlds"];
        $hiddenPlayers = $players["hidden-players"];

        return new self($period, $worlds, $mapConfig, $playersVisible, $showY, $playerHideWorlds, $hiddenPlayers);
    }

    /**
     * @return int
     */
    public function getPeriod(): int
    {
        return $this->period;
    }

    /**
     * @return array
     */
    public function getWorlds(): array
    {
        return $this->worlds;
    }

    /**
     * @return MapConfig
     */
    public function getMapConfig(): MapConfig
    {
        return $this->mapConfig;
    }

    /**
     * @return bool
     */
    public function playersVisible(): bool
    {
        return $this->playersVisible;
    }

    /**
     * @return bool
     */
    public function showY(): bool
    {
        return $this->showY;
    }

    /**
     * @return array
     */
    public function getPlayerHideWorlds(): array
    {
        return $this->playerHideWorlds;
    }

    /**
     * @return array
     */
    public function getHiddenPlayers(): array
    {
        return $this->hiddenPlayers;
    }
}