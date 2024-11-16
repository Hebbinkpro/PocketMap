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

class SchedulerSettings extends ConfigSettings
{
    private int $period;
    private int $renders;
    private int $queue;

    public function __construct(int $period, int $renders, int $queue)
    {
        $this->period = $period;
        $this->renders = $renders;
        $this->queue = $queue;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(Config $config): self
    {
        $scheduler = $config->get("renderer")["scheduler"];
        $period = intval($scheduler["run-period"] ?? 5);
        $renders = intval($scheduler["renders"] ?? 8);
        $queue = intval($scheduler["queue-size"] ?? 32);

        return new self($period, $renders, $queue);

    }

    /**
     * @return int
     */
    public function getPeriod(): int
    {
        return $this->period;
    }

    /**
     * @return int
     */
    public function getRenders(): int
    {
        return $this->renders;
    }

    /**
     * @return int
     */
    public function getQueue(): int
    {
        return $this->queue;
    }
}