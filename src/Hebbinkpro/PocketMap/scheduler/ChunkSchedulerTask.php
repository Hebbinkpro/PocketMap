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

namespace Hebbinkpro\PocketMap\scheduler;

use Generator;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\region\PartialRegion;
use Hebbinkpro\PocketMap\region\Region;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\scheduler\info\ChunkGeneratorInfo;
use Hebbinkpro\PocketMap\scheduler\info\ChunkGeneratorType;
use pocketmine\scheduler\Task;

class ChunkSchedulerTask extends Task
{

    private PocketMap $pocketMap;

    /** @var PartialRegion[] */
    private array $queuedRegions;

    /**
     * @var array<string, ChunkGeneratorInfo>
     */
    private array $chunkGenerators;

    private int $maxGeneratorYield;
    private int $maxQueueSize;

    public function __construct(PocketMap $pocketMap)
    {
        $this->pocketMap = $pocketMap;
        $this->queuedRegions = [];
        $this->chunkGenerators = [];

        $chunkScheduler = PocketMap::getSettingsManager()->getChunkScheduler();
        $this->maxGeneratorYield = $chunkScheduler->getYield();
        $this->maxQueueSize = $chunkScheduler->getQueue();
    }

    /**
     * Add all chunks in the given region to the scheduler
     * @param WorldRenderer $renderer
     * @param Region $region
     * @return bool false if the same region or full world render is added
     */
    public function addChunksByRegion(WorldRenderer $renderer, Region $region): bool
    {
        return $this->addChunks($renderer, $region->getChunks(), ChunkGeneratorType::CURRENT, $region->getName());
    }

    /**
     * Add a list of chunks to the render queue
     * @param WorldRenderer $renderer
     * @param Generator<array{int,int}> $chunks
     * @param ChunkGeneratorType $type
     * @param string|null $id the id to distinct renders
     * @return bool false if the id already exists
     */
    public function addChunks(WorldRenderer $renderer, Generator $chunks, ChunkGeneratorType $type = ChunkGeneratorType::KEY, string $id = null): bool
    {
        $world = $renderer->getWorld()->getFolderName();
        if (isset($this->chunkGenerators[$world])) return false;

        if ($id === null) $id = $world;
        $this->pocketMap->getLogger()->info("[Chunk Scheduler] Starting chunks generator for world '$id'");

        $this->chunkGenerators[$id] = new ChunkGeneratorInfo($renderer, $chunks, $type, 0);


        return true;
    }

    /**
     * Run the chunk render task
     * 1. Add chunks from generators
     * 2. Update all region cool-downs
     * 3. Start region renders until the queue is filled
     * @return void
     */
    public function onRun(): void
    {
        // add the chunks from the added iterator lists
        $this->loadChunksFromGenerators();

        // start render for all queued chunks
        // iterate over the keys to immediately remove the queued region when it has been started
        foreach (array_keys($this->queuedRegions) as $name) {
            $region = $this->queuedRegions[$name];

            // get the world renderer
            $renderer = PocketMap::getWorldRenderer($region->getWorldName());
            if ($renderer === null) continue;

            // the render did not start
            if (!$renderer->startRegionRender($region, true)) break;
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
        $loadedChunks = 0;

        // iterate over the keys to immediately remove generators when finished
        foreach (array_keys($this->chunkGenerators) as $worldName) {
            $chunkGenerator = $this->chunkGenerators[$worldName];
            $renderer = $chunkGenerator->getRenderer();
            $chunks = $chunkGenerator->getChunks();
            $type = $chunkGenerator->getType();


            while ($chunks->valid() && $loadedChunks < $this->maxGeneratorYield && count($this->queuedRegions) < $this->maxQueueSize) {


                /** @var array{int,int} $coords */
                $coords = null;

                switch ($type) {
                    case ChunkGeneratorType::KEY:
                        /** @var array{int,int} $coords */
                        $coords = $chunks->key();
                        break;
                    case ChunkGeneratorType::CURRENT:
                        /** @var array{int,int} $coords */
                        $coords = $chunks->current();
                        break;
                }

                if ($coords === null) {
                    $this->pocketMap->getLogger()->error("[Chunk Scheduler] Cannot add chunks, invalid generator type '$type->name' provided.");
                    unset($this->chunkGenerators[$worldName]);
                    break;
                }

                [$cx, $cz] = $coords;


                // increase the counters
                $loadedChunks++;
                $chunkGenerator->incrementCount();

                // add the chunk
                $this->addChunk($renderer, $cx, $cz);

                $chunks->next();
            }

            // finished with loading
            if (!$chunks->valid()) {
                $this->pocketMap->getLogger()->info("[Chunk Scheduler] Chunks generator for world '$worldName' is finished. Loaded {$chunkGenerator->getCount()} chunks.");
                unset($this->chunkGenerators[$worldName]);
            }

            // the max number of chunks in this run is loaded
            if ($loadedChunks >= $this->maxGeneratorYield || count($this->queuedRegions) >= $this->maxQueueSize) break;
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