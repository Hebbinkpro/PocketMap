<?php

namespace Hebbinkpro\PocketMap\scheduler;

use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\region\PartialRegion;
use Hebbinkpro\PocketMap\region\Region;
use Hebbinkpro\PocketMap\region\RegionChunks;
use Hebbinkpro\PocketMap\region\RegionChunksLoader;
use Hebbinkpro\PocketMap\render\AsyncChunkRenderTask;
use Hebbinkpro\PocketMap\render\AsyncRegionRenderTask;
use Hebbinkpro\PocketMap\render\AsyncRenderTask;
use Logger;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;

class RenderSchedulerTask extends Task
{
    private static array $currentRenders = [];
    private static Logger $logger;
    private PluginBase $plugin;
    /** @var AsyncRegionRenderTask[] */
    private array $currentRegionRenders;
    /** @var array{path: string, loader: RegionChunksLoader, mode: int}[] */
    private array $regionChunksLoaders;
    /** @var array<string, array{path: string, region: Region}>[] */
    private array $regionRenderQueue;
    private int $maxCurrentRenders;
    private int $maxQueueSize;

    public function __construct(PluginBase $plugin)
    {
        $this->plugin = $plugin;
        $this->currentRegionRenders = [];
        $this->regionChunksLoaders = [];
        $this->regionRenderQueue = [];

        $this->maxCurrentRenders = PocketMap::getConfigManger()->getInt("renderer.scheduler.renders", 5);
        $this->maxQueueSize = PocketMap::getConfigManger()->getInt("renderer.scheduler.queue-size", 25);

        self::$logger = $plugin->getLogger();
    }

    /**
     * Mark a region render as finished
     * @param Region $region the region that is finished
     * @return void
     */
    public static function finishRender(Region $region): void
    {
        if (!in_array($region->getName(), self::$currentRenders)) return;
        // remove the region from the list
        self::removeCurrentRender($region);

        self::$logger->debug("[Scheduler] Finished render of region: " . $region->getName());

        // start now the render for the next zoom level
        $renderer = PocketMap::getWorldRenderer($region->getWorldName());
        if (($next = $renderer->getNextZoomRegion($region)) !== null) {
            // start region render for the next region
            // use replace, so when it was already in the queue, it's added in the back
            // these regions will ALWAYS be added, even if the queue is "full",
            // but new chunk renders will wait until there is space again, so it's not that harmful for performance
            $renderer->startRegionRender($next, true, true);
        }
    }

    /**
     * Remove a region from the current renders list
     * @param Region $region the region to remove
     * @return void
     */
    private static function removeCurrentRender(Region $region): void
    {
        $key = array_search($region->getName(), self::$currentRenders);
        if ($key !== false) array_splice(self::$currentRenders, $key, 1);
    }

    /**
     * Add a region render to the scheduler
     * @param string $path the path the render is placed in
     * @param Region $region the region to render
     * @param bool $force if the render has to be added no matter how full the queue is
     * @return bool if the region is scheduled, if false, you have to manually schedule it again!
     */
    public function scheduleRegionRender(string $path, Region $region, bool $replace = false, bool $force = false): bool
    {
        // check if the region is already scheduled
        $scheduled = in_array($region->getName(), $this->regionRenderQueue);

        // when the action is not forced, and it isn't already scheduled
        if (!$force && !$scheduled && count($this->regionRenderQueue) >= $this->maxQueueSize) return false;

        // this render is already scheduled
        if (($replace || !$force) && $scheduled) {
            // and we are not allowed to replace
            if (!$replace) {
                var_dump("I'm not replacing: " . $region->getName());
                return false;
            }

            // we are going to replace the already existing render
            // remove the render that was already queued
            unset($this->regionRenderQueue[$region->getName()]);
            // remove the region from the current renders
            self::removeCurrentRender($region);
            var_dump("Replacing: " . $region->getName());
        }

        self::$currentRenders[] = $region->getName();

        // add the path and region to the queue
        $this->regionRenderQueue[$region->getName()] = [
            "path" => $path,
            "region" => $region
        ];

        return true;
    }

    /**
     * Run the render scheduler.
     * 1. Run the region chunk loaders
     * 2. Run the region renders
     */
    public function onRun(): void
    {
        // load the region chunks
        $this->loadRegionChunks();
        // run the region renders
        $this->runRegionRenders();
    }

    /**
     * Run the region chunk loaders
     * @return void
     */
    private function loadRegionChunks(): void
    {
        $notCompleted = [];

        foreach ($this->regionChunksLoaders as $rcl) {
            /** @var string $path */
            $path = $rcl["path"];
            /** @var RegionChunksLoader $loader */
            $loader = $rcl["loader"];

            // is completely loaded
            if ($loader->run()) {
                $regionChunks = $loader->getRegionChunks();
                $region = $regionChunks->getRegion();

                // it's a partial region
                if ($region instanceof PartialRegion) {
                    // get all chunks that are not loaded
                    $notLoadedChunks = $loader->getNotLoadedChunks();

                    // remove all not loaded chunks from the list
                    // otherwise, they will be marked as generated which will cause them to be black images
                    foreach ($notLoadedChunks as [$x, $z]) {
                        $region->removeChunk($x, $z);
                    }
                }

                $this->startChunkRenderTask($regionChunks, $path);
                continue;
            }

            // add to the not completed list
            $notCompleted[] = $rcl;
        }

        // clear the list and set the contents to the notCompleted regions.
        unset($this->regionChunksLoaders);
        $this->regionChunksLoaders = $notCompleted;
    }

    /**
     * Start a new chunk render
     * @param RegionChunks $regionChunks the chunks of a region
     * @param string $path the path of the render
     * @return void
     */
    public function startChunkRenderTask(RegionChunks $regionChunks, string $path): void
    {
        $task = new AsyncChunkRenderTask($regionChunks, $path);
        $this->startRenderTask($regionChunks->getRegion(), $task);
    }

    /**
     * Start a new render
     * @param Region $region the region of the render
     * @param AsyncRenderTask $task the render task
     * @return void
     */
    public function startRenderTask(Region $region, AsyncRenderTask $task): void
    {
        $this->currentRegionRenders[] = $task;

        // submit the task to the async pool
        $this->plugin->getServer()->getAsyncPool()->submitTask($task);

        self::$logger->debug("[Scheduler] Started render of region: " . $region->getName());
    }

    /**
     * Manager of all running region render.
     * - Removes finished renders
     * - Starts new renders when possible
     * @return void
     */
    private function runRegionRenders(): void
    {
        $completed = [];
        // check if the current renders are completed
        foreach ($this->currentRegionRenders as $i => $render) {
            // render has ended
            if ($render->isFinished()) {
                $completed[] = $i;
            }
        }

        // remove all completed renders
        foreach ($completed as $i) {
            unset($this->currentRegionRenders[$i]);
        }

        // add new renders until the cap is reached or no new renders are available
        while ($this->getCurrentRendersCount() < $this->maxCurrentRenders && count($this->regionRenderQueue) > 0) {
            $rr = array_shift($this->regionRenderQueue);
            $path = $rr["path"];
            /** @var Region $region */
            $region = $rr["region"];

            if ($region->isChunk()) {
                $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($region->getWorldName());
                $loader = new RegionChunksLoader($region, $world->getProvider());
                $this->regionChunksLoaders[] = [
                    "path" => $path,
                    "loader" => $loader
                ];

                continue;
            }

            $this->startRegionRenderTask($region, $path);
        }
    }

    /**
     * Get the amount of renders that is currently running
     * @return int
     */
    public function getCurrentRendersCount(): int
    {
        return count($this->currentRegionRenders) + count($this->regionChunksLoaders);
    }

    /**
     * Start a new region render
     * @param Region $region the region to render
     * @param string $path the path of the render
     * @return void
     */
    public function startRegionRenderTask(Region $region, string $path): void
    {
        $task = new AsyncRegionRenderTask($region, $path);
        $this->startRenderTask($region, $task);
    }
}