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

final class WebServerSettings extends ConfigSettings
{

    public function __construct(private string $address, private int $port)
    {
    }

    public static function fromConfig(Config $config): self
    {
        $web = $config->get("web-server", []);

        $address = strval($web["address"] ?? "127.0.0.1");
        $port = intval($web["port"] ?? 3000);

        return new self($address, $port);
    }

    /**
     * Bound address for the webserver
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Listening port for the webserver
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }
}