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

use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\textures\TerrainTextures;

class Region extends BaseRegion
{
    private string $worldName;
    private TerrainTextures $terrainTextures;
    private bool $renderChunks;


    public function __construct(string $worldName, int $zoom, int $regionX, int $regionZ, TerrainTextures $terrainTextures, bool $renderChunks = false)
    {
        parent::__construct($zoom, $regionX, $regionZ);

        $this->worldName = $worldName;
        $this->terrainTextures = $terrainTextures;
        $this->renderChunks = $renderChunks;
    }

    /**
     * Get the pixel size of a chunk inside a render of this region
     * @return int
     */
    public function getChunkPixelSize(): int
    {
        return floor(WorldRenderer::RENDER_SIZE / $this->getTotalChunks());
    }

    /**
     * Get the region name in the format: world/zoom/x,z
     * @param bool $includeWorld if the world is included in the name
     * @return string
     */
    public function getName(bool $includeWorld = true): string
    {
        if (!$includeWorld) return parent::getName();
        return $this->getWorldName() . "/" . parent::getName();
    }


    /**
     * Get the world name of the region
     * @return string
     */
    public function getWorldName(): string
    {
        return $this->worldName;
    }

    public function getNextZoomRegion(): ?Region
    {
        $base = parent::getNextZoomRegion();
        if ($base === null) return null;

        return new Region($this->worldName, $base->getZoom(), $base->getX(), $base->getZ(), $this->getTerrainTextures());
    }

    /**
     * Get the terrain textures used for rendering the region
     * @return TerrainTextures
     */
    public function getTerrainTextures(): TerrainTextures
    {
        return $this->terrainTextures;
    }

    /**
     * If all chunks inside the region should be rendered individually.
     * When false, the previous render will be used.
     * @return bool
     */
    public function renderAllChunks(): bool
    {
        return $this->isChunk() || $this->renderChunks;
    }

    public static function getByName(string $name): ?Region
    {
        $parts = explode("/", $name);

        if (count($parts) != 3) return null;

        $renderer = PocketMap::getWorldRenderer($parts[0]);
        if ($renderer === null) return null;

        $zoom = intval($parts[1]);
        $pos = explode(",", $parts[2]);
        if (count($pos) < 2) return null;
        $x = intval($pos[0]);
        $z = intval($pos[1]);

        return $renderer->getRegion($zoom, $x, $z);
    }
}