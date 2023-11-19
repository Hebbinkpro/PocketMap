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
 * Copyright (C) 2023 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\region;

use Generator;
use Hebbinkpro\PocketMap\textures\TerrainTextures;

/**
 * A normal Region, but with a generator looping through set chunks instead of all chunks that are in the region.
 * This region type will also make sure all chunks in the region will be rendered by default.
 */
class PartialRegion extends Region
{
    /** @var array<array{int,int}> */
    private array $chunks;

    public function __construct(string $worldName, int $zoom, int $regionX, int $regionZ, TerrainTextures $terrainTextures, bool $renderChunks = true)
    {
        parent::__construct($worldName, $zoom, $regionX, $regionZ, $terrainTextures, $renderChunks);
        $this->chunks = [];
    }

    /**
     * Add a chunk to the chunks list
     * @param int $chunkX
     * @param int $chunkZ
     * @return void
     */
    public function addChunk(int $chunkX, int $chunkZ): void
    {
        // not inside the region
        if (!$this->isChunkInRegion($chunkX, $chunkZ)) return;

        $pos = [$chunkX, $chunkZ];
        if (!in_array($pos, $this->chunks, true)) {
            $this->chunks[] = [$chunkX, $chunkZ];
        }
    }

    /**
     * Remove a chunk from the list
     * @param int $chunkX
     * @param int $chunkZ
     * @return void
     */
    public function removeChunk(int $chunkX, int $chunkZ): void
    {
        $pos = [$chunkX, $chunkZ];
        $key = array_search($pos, $this->chunks, true);
        if (!is_int($key)) return;

        array_splice($this->chunks, $key, 1);
    }

    /**
     * Yield all chunk coordinates from the chunks in the list
     * @return Generator<array{int,int}>
     */
    public function getChunks(): Generator
    {
        // loop through all items inside the chunk list and yield the x and z position.
        foreach ($this->chunks as $pos) {
            yield $pos;
        }
    }
}