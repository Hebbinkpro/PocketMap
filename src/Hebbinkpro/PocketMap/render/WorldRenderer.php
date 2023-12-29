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

namespace Hebbinkpro\PocketMap\render;

use Hebbinkpro\PocketMap\region\PartialRegion;
use Hebbinkpro\PocketMap\region\Region;
use Hebbinkpro\PocketMap\scheduler\ChunkSchedulerTask;
use Hebbinkpro\PocketMap\scheduler\RenderSchedulerTask;
use Hebbinkpro\PocketMap\textures\TerrainTextures;
use pocketmine\block\tile\Tile;
use pocketmine\entity\Entity;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\World;

class WorldRenderer
{
    /**
     * The lowest zoom level available
     */
    public const MIN_ZOOM = 0;

    /**
     * The maximum zoom level
     */
    public const MAX_ZOOM = 8;

    /**
     * The size of a render in pixels
     */
    public const RENDER_SIZE = 256;

    private World $world;
    private TerrainTextures $terrainTextures;
    private string $renderPath;
    private RenderSchedulerTask $scheduler;
    private ChunkSchedulerTask $chunkRenderer;

    public function __construct(World $world, TerrainTextures $terrainTextures, string $renderPath, RenderSchedulerTask $scheduler, ChunkSchedulerTask $chunkRenderer)
    {
        $this->world = $world;
        $this->terrainTextures = $terrainTextures;
        $this->renderPath = $renderPath;
        $this->scheduler = $scheduler;
        $this->chunkRenderer = $chunkRenderer;
    }

    /**
     * Start a complete render of the world
     * @return void
     */
    public function startFullWorldRender(): void
    {
        $this->chunkRenderer->addChunks($this, $this->world->getProvider()->getAllChunks());
    }

    /**
     * Schedule a render of the given region.
     * @param Region $region
     * @param bool $replace
     * @param bool $force
     * @return bool
     * @internal
     */
    public function startRegionRender(Region $region, bool $replace = false, bool $force = false): bool
    {
        if (!is_dir($this->renderPath . $region->getZoom())) mkdir($this->renderPath . $region->getZoom());

        return $this->scheduler->scheduleRegionRender($this->renderPath, $region, $replace, $force);
    }

    /**
     * Get the world
     * @return World
     */
    public function getWorld(): World
    {
        return $this->world;
    }

    /**
     * Get the render path of this world
     * @return string
     */
    public function getRenderPath(): string
    {
        return $this->renderPath;
    }

    /**
     * Get if the given chunk is rendered
     * @param int $x the x coordinate of the chunk
     * @param int $z the z coordinate of the chunk
     * @return bool
     */
    public function isChunkRendered(int $x, int $z): bool
    {
        $file = $this->renderPath . self::MIN_ZOOM . "/$x,$z.png";
        return is_file($file);
    }

    /**
     * Get a region with the size of a single chunk
     * @param int $x the x coordinate of the chunk
     * @param int $z the z coordinate of the chunk
     * @return PartialRegion
     */
    public function getChunkRegion(int $x, int $z): PartialRegion
    {
        return new PartialRegion($this->world->getFolderName(), self::MIN_ZOOM, $x, $z, $this->terrainTextures);
    }

    public function getRegion(int $zoom, int $x, int $z, bool $renderChunks = false): Region
    {
        return new Region($this->world->getFolderName(), $zoom, $x, $z, $this->terrainTextures, $renderChunks);
    }

    /**
     * Saves a chunk to the world provider.
     * @param int $chunkX
     * @param int $chunkZ
     * @return void
     */
    public function saveChunk(int $chunkX, int $chunkZ): void
    {
        // chunk isn't loaded, so it is already saved
        if (!$this->world->isChunkLoaded($chunkX, $chunkZ)) return;

        $chunk = $this->world->getChunk($chunkX, $chunkZ);
        if ($chunk === null) return;

        // store the chunk, logic from pocketmine\world\World->saveChunks()
        $this->world->getProvider()->saveChunk($chunkX, $chunkZ, new ChunkData(
            $chunk->getSubChunks(),
            $chunk->isPopulated(),
            array_map(fn(Entity $e) => $e->saveNBT(), array_filter($this->world->getChunkEntities($chunkX, $chunkZ), fn(Entity $e) => $e->canSaveWithChunk())),
            array_map(fn(Tile $t) => $t->saveNBT(), $chunk->getTiles()),
        ), $chunk->getTerrainDirtyFlags());
    }
}