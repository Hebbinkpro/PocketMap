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

use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\settings\WebServerSettings;
use Hebbinkpro\WebServer\exception\WebServerException;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\router\Router;
use Hebbinkpro\WebServer\WebServer;

class WebServerManager
{
    public const WEB_VERSION = 1.2;

    private PocketMap $pocketMap;
    private WebServerSettings $setting;
    private WebServer $server;

    public function __construct(PocketMap $pocketMap, WebServerSettings $settings)
    {
        $this->pocketMap = $pocketMap;
        $this->setting = $settings;
    }

    /**
     * @throws WebServerException
     */
    public function createServer(): void
    {
        $serverInfo = new HttpServerInfo($this->setting->getAddress(), $this->setting->getPort());
        $this->addRoutes($serverInfo->getRouter());


        $this->server = new WebServer($this->pocketMap, $serverInfo);
        $this->server->detectSSL();
    }

    /**
     * @throws WebServerException
     */
    public function addRoutes(Router $router): void
    {
        $webFolder = $this->pocketMap->getWebFolder();

        // main route
        $router->getFile("/", $webFolder . "pages/index.html");

        // all static files used by web pages
        $router->getStatic("/static", $webFolder . "static");


        // register the api router

        $router->route("/api/pocketmap", $this->getAPIRouter());
    }

    /**
     * @throws WebServerException
     */
    public function getAPIRouter(): Router
    {
        $webFolder = $this->pocketMap->getWebFolder();
        $apiFolder = $this->pocketMap->getTmpApiFolder();
        $rendersFolder = $this->pocketMap->getRendersFolder();
        $markersFolder = $this->pocketMap->getMarkersFolder();

        $router = new Router();
        $router->getFile("/config", $webFolder . "config.json");

        // get the world data
        $router->getFile("/worlds", $apiFolder . "worlds.json", "[]");

        // get player data
        $router->getFile("/players", $apiFolder . "players.json", "[]");

        // get the player heads
        $router->getStatic("/players/skin", $apiFolder . "skin");

        // get image renders
        $router->getStatic("/render", $rendersFolder);

        // get markers
        $router->getFile("/markers", $markersFolder . "markers.json", "[]");
        // get marker icons
        $router->getFile("/markers/icons", $markersFolder . "icons/icons.json", "[]");
        $router->getStatic("/markers/icons", $markersFolder . "icons");
        return $router;
    }

    /**
     * Start the webserver
     * @return bool if the webserver has been started
     */
    public function startServer(): bool
    {
        if (!isset($this->server) || $this->server->isStarted()) return false;

        try {
            $this->server->start();
            return true;
        } catch (WebServerException) {
            return false;
        }
    }

    /**
     *
     * @return void
     */
    public function stopServer(): void
    {
        if (isset($this->server) && $this->server->isStarted()) {
            $this->server->close();
        }
    }
}