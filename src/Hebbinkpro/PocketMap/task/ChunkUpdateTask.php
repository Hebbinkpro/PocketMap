<?php

namespace Hebbinkpro\PocketMap\task;

use Hebbinkpro\PocketMap\render\Region;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class ChunkUpdateTask extends Task
{
    public const COOLDOWN_TIME = 60;

    private PocketMap $PocketMap;

    /** @var Region[] */
    private array $updatedRegions;
    private array $cooldown;

    public function __construct(PocketMap $PocketMap)
    {
        $this->PocketMap = $PocketMap;
        $this->updatedRegions = [];
        $this->cooldown = [];
    }

    public function addChunk(World $world, int $chunkX, int $chunkZ): void
    {
        // world is already rendering, no need to add it...
        if (in_array($world->getFolderName(), $this->PocketMap->getRenderScheduler()->getFullWorldRenders())) return;

        $worldName = $world->getFolderName();

        $renderer = $this->PocketMap->getWorldRenderer($worldName);
        if ($renderer === null) return;

        // add all regions the chunk is in to the queue
        foreach (array_keys(WorldRenderer::ZOOM_LEVELS) as $zoom) {
            $region = $renderer->getRegionFromChunk($zoom, $chunkX, $chunkZ);
            $this->addRegion($region);
        }
    }

    /**
     * Adds a region to the queue if it does not yet exist
     * @param Region $region
     * @return void
     */
    public function addRegion(Region $region): void
    {
        // region cannot be added to the queue
        if ($this->isAdded($region)) return;

        $this->PocketMap->getLogger()->debug("[ChunkUpdate] Added region to the queue: " . $region->getRegionX() . "," . $region->getRegionZ() . ", zoom: " . $region->getZoom() . ", world: " . $region->getWorldName());

        // add the region to the queue
        $this->updatedRegions[] = $region;
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
            $renderer = $this->PocketMap->getWorldRenderer($region->getWorldName());
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