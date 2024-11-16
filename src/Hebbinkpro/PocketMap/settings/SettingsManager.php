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

use pocketmine\utils\Config;

class SettingsManager
{
    private PocketMapSettings $pocketMap;
    private ChunkSchedulerSettings $chunkScheduler;
    private SchedulerSettings $scheduler;
    private TextureSettings $texture;
    private WebApiSettings $api;
    private WebServerSettings $webServer;

    /**
     * Load all settings from the given config
     * @param Config $config
     * @return void
     */
    public function load(Config $config): void
    {
        $this->pocketMap = PocketMapSettings::fromConfig($config);
        $this->chunkScheduler = ChunkSchedulerSettings::fromConfig($config);
        $this->scheduler = SchedulerSettings::fromConfig($config);
        $this->texture = TextureSettings::fromConfig($config);
        $this->api = WebApiSettings::fromConfig($config);
        $this->webServer = WebServerSettings::fromConfig($config);
    }

    /**
     * @return PocketMapSettings
     */
    public function getPocketMap(): PocketMapSettings
    {
        return $this->pocketMap;
    }

    /**
     * @return ChunkSchedulerSettings
     */
    public function getChunkScheduler(): ChunkSchedulerSettings
    {
        return $this->chunkScheduler;
    }

    /**
     * @return SchedulerSettings
     */
    public function getScheduler(): SchedulerSettings
    {
        return $this->scheduler;
    }

    /**
     * @return TextureSettings
     */
    public function getTexture(): TextureSettings
    {
        return $this->texture;
    }

    /**
     * @return WebApiSettings
     */
    public function getApi(): WebApiSettings
    {
        return $this->api;
    }

    /**
     * @return WebServerSettings
     */
    public function getWebServer(): WebServerSettings
    {
        return $this->webServer;
    }


}