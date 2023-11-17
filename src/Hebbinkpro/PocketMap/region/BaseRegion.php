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

class BaseRegion
{

    private int $zoom;
    private int $x;
    private int $z;

    public function __construct(int $zoom, int $regionX, int $regionZ)
    {
        $this->zoom = $zoom;
        $this->x = $regionX;
        $this->z = $regionZ;
    }

    /**
     * Yields all chunk coordinates that are inside the region
     * @return int[]|Generator
     */
    public function getChunks(): Generator|array
    {
        $minX = $this->x * $this->getTotalChunks();
        $minZ = $this->z * $this->getTotalChunks();

        for ($x = $minX; $x < $minX + $this->getTotalChunks(); $x++) {
            for ($z = $minZ; $z < $minZ + $this->getTotalChunks(); $z++) {
                yield [$x, $z];
            }
        }
    }

    /**
     * Get the total amount of chunks inside the region
     * @return int
     */
    public function getTotalChunks(): int
    {
        return intval(pow(2, $this->zoom));
    }

    /**
     * Get the coordinates of a chunk inside the region.
     * @param int $worldChunkX the x coordinate of the chunk inside the worldf
     * @param int $worldChunkZ the z coordinate of the chunk inside the world
     * @return int[]
     */
    public function getRegionChunkCoords(int $worldChunkX, int $worldChunkZ): array
    {
        return [
            $worldChunkX - ($this->x * $this->getTotalChunks()),
            $worldChunkZ - ($this->z * $this->getTotalChunks())
        ];
    }

    /**
     * Check if a chunk is inside the region.
     * @param int $chunkX
     * @param int $chunkZ
     * @return bool
     */
    public function isChunkInRegion(int $chunkX, int $chunkZ): bool
    {
        $minX = $this->x * $this->getTotalChunks();
        $minZ = $this->z * $this->getTotalChunks();
        $maxX = ($this->x + 1) * $this->getTotalChunks();
        $maxZ = ($this->z + 1) * $this->getTotalChunks();

        return $minX <= $chunkX && $chunkX < $maxX
            && $minZ <= $chunkZ && $chunkZ < $maxZ;
    }

    /**
     * Get the zoom level of the region
     * @return int
     */
    public function getZoom(): int
    {
        return $this->zoom;
    }

    /**
     * Get the X position of the region
     * @return int
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * Get the Z position of the region
     * @return int
     */
    public function getZ(): int
    {
        return $this->z;
    }

    public function getNextZoomRegion(): ?BaseRegion
    {
        $nextZoom = $this->zoom + 1;
        // there is no smaller zoom available
        if ($nextZoom > 8) return null;

        $nextX = floor($this->x / 2);
        $nextZ = floor($this->z / 2);

        return new BaseRegion($nextZoom, $nextX, $nextZ);
    }

    /**
     * Get if the region only exists out of a single chunk.
     * @return bool
     */
    public function isChunk(): bool
    {
        return $this->getTotalChunks() == 1;
    }

    /**
     * Get the region name in the format: zoom/x,z
     * @return string
     */
    public function getName(): string
    {
        return $this->zoom . "/" . $this->x . "," . $this->z;
    }
}