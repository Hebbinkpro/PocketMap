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

namespace Hebbinkpro\PocketMap\scheduler;

use Generator;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\region\PartialRegion;
use Hebbinkpro\PocketMap\region\Region;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use PhpParser\Node\Expr\Isset_;
use pocketmine\scheduler\Task;

class ChunkSchedulerTask extends Task
{
    public const CHUNK_GENERATOR_KEY = 0;
    public const CHUNK_GENERATOR_CURRENT = 1;

    private PocketMap $pocketMap;

    /** @var PartialRegion[] */
    private array $queuedRegions;

    /**
     * @var array<array{renderer: WorldRenderer, chunks: Generator, type: int}>
     */
    private array $chunkGenerators;

    private int $maxQueueSize;

    public function __construct(PocketMap $pocketMap)
    {
        $this->pocketMap = $pocketMap;
        $this->queuedRegions = [];
        $this->chunkGenerators = [];

        $this->maxQueueSize = PocketMap::getConfigManger()->getInt("renderer.chunk-scheduler.queue-size", 256);
    }

    /**
     * Add a list of chunks to the render queue
     * @param WorldRenderer $renderer
     * @param Generator $chunks
     * @param int $type
     * @param string|null $id the id to distinct renders
     * @return bool false if the id already exists
     */
    public function addChunks(WorldRenderer $renderer, Generator $chunks, int $type = self::CHUNK_GENERATOR_KEY, string $id = null): bool
    {
        $world = $renderer->getWorld()->getFolderName();
        if (isset($this->chunkGenerators[$world])) return false;

        if ($id == null) $id = $world;
        $this->pocketMap->getLogger()->info("[Chunk Scheduler] Starting chunks generator for world '$id'");

        $this->chunkGenerators[$id] = [
            "renderer" => $renderer,
            "chunks" => $chunks,
            "type" => $type,
            "count" => 0
        ];


        return true;
    }

    /**
     * Add all chunks in the given region to the scheduler
     * @param WorldRenderer $renderer
     * @param Region $region
     * @return bool false if the same region or full world render is added
     */
    public function addChunksByRegion(WorldRenderer $renderer, Region $region): bool {
        return $this->addChunks($renderer, $region->getChunks(), self::CHUNK_GENERATOR_CURRENT, $region->getName());
    }

    /**
     * Run the chunk render task
     * 1. Add chunks from the generators
     * 2. Update all region cool-downs
     * 3. Start region renders until the queue is filled
     * @return void
     */
    public function onRun(): void
    {
        // add the chunks from the added iterator lists
        $this->loadChunksFromGenerators();

        $started = [];

        // start render for all queued chunks
        foreach ($this->queuedRegions as $name => $region) {

            // get the world renderer
            $renderer = $this->pocketMap->getWorldRenderer($region->getWorldName());
            if (!$renderer) continue;

            // the render did not start
            if (!$renderer->startRegionRender($region, true)) break;

            $this->pocketMap->getLogger()->debug("[Chunk Render] Added chunk to the scheduler: " . $region->getName());
            $started[] = $name;

        }

        // remove all the started regions
        foreach ($started as $name) {
            unset($this->queuedRegions[$name]);
        }
    }

    /**
     * Yield some values from the generators and add the chunks.
     * We yield up to a max cap to prevent the server from lagging when a lot of chunks are inside the generators.
     * @return void
     */
    private function loadChunksFromGenerators(): void
    {
        $finished = [];

        $maxLoad = PocketMap::getConfigManger()->getInt("renderer.chunk-scheduler.generator-yield", 10);
        $loaded = 0;

        foreach ($this->chunkGenerators as $worldName => $worldChunks) {
            /** @var WorldRenderer $renderer */
            $renderer = $worldChunks["renderer"];
            /** @var Generator $chunks */
            $chunks = $worldChunks["chunks"];
            /** @var int $type */
            $type = $worldChunks["type"];

            while ($chunks->valid() && $loaded < $maxLoad && count($this->queuedRegions) < $this->maxQueueSize) {
                [$cx,$cz] = match($type) {
                    self::CHUNK_GENERATOR_KEY => $chunks->key(),
                    self::CHUNK_GENERATOR_CURRENT => $chunks->current(),
                    default => [null, null]
                };

                // invalid generator type
                if ([$cx,$cz] === [null, null]) {
                    $this->pocketMap->getLogger()->error("[Chunk Scheduler] Cannot add chunks with invalid generator type: $type");
                    $finished[] = $worldName;
                    break;
                }

                $this->addChunk($renderer, $cx, $cz);
                var_dump("Added chunk $cx,$cz in world '$worldName' to the queue");
                $chunks->next();
                $loaded++;
                $worldChunks["count"]++;
            }

            // finished with loading
            if (!$chunks->valid()) {
                $this->pocketMap->getLogger()->info("[Chunk Scheduler] Chunks generator for world '{$worldName}' is finished. Loaded {$worldChunks["count"]} chunks.");
                $finished[] = $worldName;
            }

            // the max amount of chunks of this run is loaded
            if ($loaded >= $maxLoad || count($this->queuedRegions) >= $this->maxQueueSize) break;
        }

        foreach ($finished as $worldName) {
            unset($this->chunkGenerators[$worldName]);
        }
    }

    /**
     * Add A chunk region to the region queue
     * @param WorldRenderer $renderer
     * @param int $chunkX
     * @param int $chunkZ
     * @return void
     */
    public function addChunk(WorldRenderer $renderer, int $chunkX, int $chunkZ): void
    {
        $region = $renderer->getChunkRegion($chunkX, $chunkZ);
        $region->addChunk($chunkX, $chunkZ);

        $this->queuedRegions[$region->getName()] = $region;
    }
}