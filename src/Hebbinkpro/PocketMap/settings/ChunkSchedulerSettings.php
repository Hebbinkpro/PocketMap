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

class ChunkSchedulerSettings extends ConfigSettings
{
    private int $period;
    private int $cooldown;
    private int $yield;
    private int $cpr;
    private int $queue;

    public function __construct(int $period, int $cooldown, int $yield, int $cpr, int $queue)
    {
        $this->period = $period;
        $this->cooldown = $cooldown;
        $this->yield = $yield;
        $this->cpr = $cpr;
        $this->queue = $queue;

    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(Config $config): self
    {
        $scheduler = $config->get("renderer")["chunk-scheduler"];
        $period = intval($scheduler["run-period"] ?? 10);
        $cooldown = intval($scheduler["chunk-cooldown"] ?? 60);
        $yield = intval($scheduler["generator-yield"] ?? 32);
        $cpr = intval($scheduler["chunks-per-run"] ?? 128);
        $queue = intval($scheduler["queue-size"] ?? 256);

        return new self($period, $cooldown, $yield, $cpr, $queue);

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
    public function getCooldown(): int
    {
        return $this->cooldown;
    }

    /**
     * @return int
     */
    public function getYield(): int
    {
        return $this->yield;
    }

    /**
     * @return int
     */
    public function getCpr(): int
    {
        return $this->cpr;
    }

    /**
     * @return int
     */
    public function getQueue(): int
    {
        return $this->queue;
    }
}