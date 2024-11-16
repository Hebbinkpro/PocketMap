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

class PocketMapSettings extends ConfigSettings
{
    public function __construct(private bool $loadWorlds, private bool $debug)
    {
    }

    public static function fromConfig(Config $config): self
    {
        $loadWorlds = boolval($config->get("load-worlds"));
        $debug = boolval($config->get("debug"));
        return new self($loadWorlds, $debug);
    }

    /**
     * @return bool
     */
    public function loadWorlds(): bool
    {
        return $this->loadWorlds;
    }

    /**
     * @return bool
     */
    public function debugEnabled(): bool
    {
        return $this->debug;
    }
}