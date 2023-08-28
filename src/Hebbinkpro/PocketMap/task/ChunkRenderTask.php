<?php

namespace Hebbinkpro\PocketMap\task;

use Generator;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\render\PartialRegion;
use Hebbinkpro\PocketMap\render\Region;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use pocketmine\scheduler\Task;

class ChunkRenderTask extends Task
{
    public const CACHE_FILE = "tmp/regions/render.txt";


    private PocketMap $pocketMap;

    /** @var Region[] */
    private array $queuedRegions;

    /**
     * @var array{renderer: WorldRenderer, chunks: Generator}[]
     */
    private array $chunkGenerators;

    private bool $enableCache;

    private int $maxQueueSize;

    public function __construct(PocketMap $pocketMap)
    {
        $this->pocketMap = $pocketMap;
        $this->queuedRegions = [];
        $this->chunkGenerators = [];

        $this->enableCache = PocketMap::getConfigManger()->getBool("renderer.chunk-renderer.region-cache", true);
        $this->maxQueueSize = PocketMap::getConfigManger()->getInt("chunk-loader.queue-size", 256);

        if ($this->enableCache) $this->readFromCache();
    }

    /**
     * Read the contents of the cache file and restore the contents inside the updated regions list.
     * This allows us to start directly with rendering if there were chunks added just before the server closed.
     * @return void
     */
    private function readFromCache(): void
    {
        $cacheFile = PocketMap::getFolder() . self::CACHE_FILE;
        if (!file_exists($cacheFile)) return;

        $data = file_get_contents($cacheFile);
        $this->queuedRegions = unserialize($data);
        $this->pocketMap->getLogger()->debug("Restored " . count($this->queuedRegions) . " regions from the cache");
    }

    /**
     * Add a list of chunks to the render queue
     * @param WorldRenderer $renderer
     * @param Generator $chunks
     * @return void
     */
    public function addChunks(WorldRenderer $renderer, Generator $chunks): void
    {
        $this->pocketMap->getLogger()->debug("Adding chunks generator for world: {$renderer->getWorld()->getFolderName()}");
        $this->chunkGenerators[$renderer->getWorld()->getFolderName()] = [
            "renderer" => $renderer,
            "chunks" => $chunks
        ];
    }

    /**
     * Check if a region is already in the queue
     * @param Region $region
     * @return bool
     */
    public function isAdded(Region $region): bool
    {
        return array_key_exists("$region", $this->queuedRegions);
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

            $isStarted = $renderer->startRegionRender($region);

            // the render did not start
            if (!$isStarted) {
                // the queue is full, so no other has to try it
                break;
            }

            $this->pocketMap->getLogger()->debug("[Chunk Render] Added render of region: $region to the scheduler");
            $started[] = $name;

        }

        // remove all the started regions
        foreach ($started as $name) {
            unset($this->queuedRegions[$name]);
        }

        if ($this->enableCache) {
            // update the cache
            $cacheFile = PocketMap::getFolder() . self::CACHE_FILE;
            file_put_contents($cacheFile, serialize($this->queuedRegions));
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

        $maxLoad = PocketMap::getConfigManger()->getInt("renderer.chunk-renderer.generator-yield", 10);
        $loaded = 0;

        foreach ($this->chunkGenerators as $worldName => $worldChunks) {
            /** @var WorldRenderer $renderer */
            $renderer = $worldChunks["renderer"];
            /** @var Generator $chunks */
            $chunks = $worldChunks["chunks"];

            while ($chunks->valid() && $loaded < $maxLoad && count($this->queuedRegions) < $this->maxQueueSize) {
                [$cx, $cz] = $chunks->key();
                $this->addChunk($renderer, $cx, $cz);
                $chunks->next();
                $loaded++;
            }

            // finished with loading
            if (!$chunks->valid()) {
                $this->pocketMap->getLogger()->debug("Chunks generator for world: {$renderer->getWorld()->getFolderName()} is finished");
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
     * Add a chunk to the render queue
     * @param WorldRenderer $renderer
     * @param int $chunkX
     * @param int $chunkZ
     * @return void
     */
    public function addChunk(WorldRenderer $renderer, int $chunkX, int $chunkZ): void
    {
        // add all regions the chunk is in to the queue
        foreach (array_keys(WorldRenderer::ZOOM_LEVELS) as $zoom) {
            $region = $renderer->getPartialRegion($zoom, $chunkX, $chunkZ);
            $this->addPartialRegion($region, $chunkX, $chunkZ);
        }
    }

    /**
     * Adds a partial region to the queue
     * @param PartialRegion $region
     * @param int $chunkX
     * @param int $chunkZ
     * @return void
     */
    public function addPartialRegion(PartialRegion $region, int $chunkX, int $chunkZ): void
    {

        $queuedRegion = $this->getQueuedRegion($region);
        // the region is already stored
        if ($queuedRegion === null) {
            $queuedRegion = $region;
            $this->pocketMap->getLogger()->debug("[Chunk Render] Added region to the queue: $region, world: " . $region->getWorldName());
            $this->queuedRegions["$region"] = $region;
        }
        $this->pocketMap->getLogger()->debug("[Chunk Render] Added chunk: $chunkX,$chunkZ, to region: $queuedRegion");

        // add the chunk to the stored region
        $queuedRegion->addChunk($chunkX, $chunkZ);
    }

    /**
     * Get a queued region by a similar region
     * @param Region $region
     * @return Region|null
     */
    public function getQueuedRegion(Region $region): ?Region
    {
        return $this->queuedRegions["$region"] ?? null;
    }
}