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

namespace Hebbinkpro\PocketMap\scheduler\info;

use Hebbinkpro\PocketMap\region\RegionChunksLoader;

class ChunkLoaderInfo
{
    private string $path;
    private RegionChunksLoader $loader;
    private int $mode;

    /**
     * @param string $path
     * @param RegionChunksLoader $loader
     * @param int $mode
     */
    public function __construct(string $path, RegionChunksLoader $loader, int $mode)
    {
        $this->path = $path;
        $this->loader = $loader;
        $this->mode = $mode;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return RegionChunksLoader
     */
    public function getLoader(): RegionChunksLoader
    {
        return $this->loader;
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
    }


}