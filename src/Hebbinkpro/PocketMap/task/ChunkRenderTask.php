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
    public const CACHE_FILE = "regions/render.txt";


    private PocketMap $pocketMap;

    /** @var Region[] */
    private array $updatedRegions;

    /**
     * @var array{renderer: WorldRenderer, chunks: Generator}[]
     */
    private array $chunkGenerators;

    private array $cooldown;

    private int $cooldownTime;
    private bool $enableCache;

    public function __construct(PocketMap $pocketMap)
    {
        $this->pocketMap = $pocketMap;
        $this->updatedRegions = [];
        $this->cooldown = [];
        $this->chunkGenerators = [];

        $this->cooldownTime = PocketMap::getConfigManger()->getInt("renderer.chunk-renderer.region-cooldown", 60);
        $this->enableCache = PocketMap::getConfigManger()->getBool("renderer.chunk-renderer.region-cache", true);


        if ($this->enableCache) $this->readFromCache();
    }

    /**
     * Read the contents of the cache file and restore the contents inside the updated regions list.
     * This allows us to start directly with rendering if there were chunks added just before the server closed.
     * @return void
     */
    private function readFromCache(): void
    {
        $cacheFile = PocketMap::getTmpDataPath() . self::CACHE_FILE;
        if (!file_exists($cacheFile)) return;

        $data = file_get_contents($cacheFile);
        $this->updatedRegions = unserialize($data);
        $this->pocketMap->getLogger()->debug("Restored " . count($this->updatedRegions) . " regions from the cache");
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
        foreach ($this->updatedRegions as $r) {
            if ($region->equals($r)) return true;
        }

        return false;
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

        // update the cool-downs
        $this->updateCooldown();

        $started = [];

        // start render for all queued chunks
        foreach ($this->updatedRegions as $i => $region) {
            // region is still on cooldown
            if ($this->hasCooldown($region)) continue;

            // get the world renderer
            $renderer = $this->pocketMap->getWorldRenderer($region->getWorldName());
            if (!$renderer) continue;

            $isStarted = $renderer->startRegionRender($region);

            // the render did not start
            if (!$isStarted) {
                // the queue is full, so no other has to try it
                break;
            }

            $this->pocketMap->getLogger()->debug("Started render of region: " . $region->getZoom() . "/" . $region->getX() . "," . $region->getZ());
            // add region to the cooldown list
            $this->cooldown[] = [$region, time()];
            $started[] = $i;

        }

        // remove all the started regions
        foreach ($started as $i) {
            unset($this->updatedRegions[$i]);
        }

        // update the cache
        $cacheFile = PocketMap::getTmpDataPath() . self::CACHE_FILE;
        file_put_contents($cacheFile, serialize($this->updatedRegions));
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

            while ($chunks->valid() && $loaded < $maxLoad) {
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
            if ($loaded >= $maxLoad) {
                return;
            }
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

        $storedRegion = $this->getStoredRegion($region);
        // the region is already stored
        if ($storedRegion === null) {
            $storedRegion = $region;
            $this->pocketMap->getLogger()->debug("[ChunkUpdate] Added region to the queue: " . $region->getX() . "," . $region->getZ() . ", zoom: " . $region->getZoom() . ", world: " . $region->getWorldName());
            $this->updatedRegions[] = $region;
        }

        // add the chunk to the stored region
        $storedRegion->addChunk($chunkX, $chunkZ);
    }

    /**
     * Get a saved region by a similar region
     * @param Region $region
     * @return Region|null
     */
    public function getStoredRegion(Region $region): ?Region
    {
        foreach ($this->updatedRegions as $r) {
            if ($region->equals($r)) return $r;
        }

        return null;
    }

    /**
     * Check if the cool-downs still hold
     * @return void
     */
    private function updateCooldown(): void
    {
        $onCooldown = [];

        // loop through all cool-downs and remove the one's that are expired
        foreach ($this->cooldown as [$r, $time]) {
            if (time() - $time < $this->cooldownTime) {
                $onCooldown[] = [$r, $time];
            }
        }

        $this->cooldown = $onCooldown;
    }

    /**
     * Get if a region has a cool-down
     * @param Region $region
     * @return bool if the region has a cool down
     */
    public function hasCooldown(Region $region): bool
    {
        foreach ($this->cooldown as [$r, $time]) {
            if ($region->equals($r)) return true;
        }

        return false;
    }
}