<?php

namespace Hebbinkpro\PocketMap\task;

use Hebbinkpro\PocketMap\render\PartialRegion;
use Hebbinkpro\PocketMap\render\Region;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\scheduler\Task;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

class ChunkUpdateTask extends Task
{
    public const COOLDOWN_TIME = 60;

    private PocketMap $pocketMap;

    /** @var Region[] */
    private array $updatedRegions;
    private array $cooldown;

    public function __construct(PocketMap $PocketMap)
    {
        $this->pocketMap = $PocketMap;
        $this->updatedRegions = [];
        $this->cooldown = [];
    }

    public function addChunk(World $world, Chunk $chunk, int $chunkX, int $chunkZ): void
    {
        $worldName = $world->getFolderName();
        // world is already rendering, no need to add it...
        if (in_array($worldName, $this->pocketMap->getRenderScheduler()->getFullWorldRenders())) return;

        // get the world renderer of the world
        $renderer = $this->pocketMap->getWorldRenderer($worldName);
        if ($renderer === null) return;

        // add all regions the chunk is in to the queue
        foreach (array_keys(WorldRenderer::ZOOM_LEVELS) as $zoom) {
            $region = $renderer->getPartialRegion($zoom, $chunkX, $chunkZ);
            $this->addPartialRegion($region, $chunk, $chunkX, $chunkZ);
        }
    }

    /**
     * Adds a partial region to the queue
     * @param PartialRegion $region
     * @param Chunk $chunk
     * @return void
     */
    public function addPartialRegion(PartialRegion $region, Chunk $chunk, int $chunkX, int $chunkZ): void
    {

        $storedRegion = $this->getStoredRegion($region);
        // the region is already stored
        if ($storedRegion === null) {
            $storedRegion = $region;
            $this->pocketMap->getLogger()->debug("[ChunkUpdate] Added region to the queue: " . $region->getRegionX() . "," . $region->getRegionZ() . ", zoom: " . $region->getZoom() . ", world: " . $region->getWorldName());
            $this->updatedRegions[] = $region;
        }

        // add the chunk to the stored region
        $storedRegion->addChunk($chunk, $chunkX, $chunkZ);
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
     * Get a saved region by a similar region
     * @param Region $region
     * @return Region|null
     */
    public function getStoredRegion(Region $region): ?Region {
        foreach ($this->updatedRegions as $r) {
            if ($region->equals($r)) return $r;
        }

        return null;
    }

    public function onRun(): void
    {
        // update the cool-downs
        $this->updateCooldown();

        $notStarted = [];

        // start render for all queued chunks
        foreach ($this->updatedRegions as $region) {
            // region is still on cooldown
            if ($this->hasCooldown($region)) continue;

            // get the world renderer
            $renderer = $this->pocketMap->getWorldRenderer($region->getWorldName());
            if (!$renderer) continue;

            $started = $renderer->startRegionRender($region);

            // the render did not start
            if (!$started) {
                $notStarted[] = $region;
                continue;
            }

            // add region to the cooldown list
            $this->cooldown[] = [$region, time()];
        }

        // set all regions that are not started back in the queue
        $this->updatedRegions = $notStarted;

    }

    private function updateCooldown(): void
    {
        $onCooldown = [];

        foreach ($this->cooldown as [$r, $time]) {
            if (time() - $time < self::COOLDOWN_TIME) {
                $onCooldown[] = [$r, $time];
            }
        }

        $this->cooldown = $onCooldown;
    }

    public function hasCooldown(Region $region): bool
    {
        foreach ($this->cooldown as [$r, $time]) {
            if ($region->equals($r)) return true;
        }

        return false;
    }
}