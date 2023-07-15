<?php

namespace Hebbinkpro\PocketMap\task;

use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\render\Region;
use Hebbinkpro\PocketMap\render\RegionChunks;
use Hebbinkpro\PocketMap\render\RegionChunksLoader;
use Hebbinkpro\PocketMap\utils\ColorMapParser;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;

class RenderSchedulerTask extends Task
{
    private PluginBase $plugin;

    /** @var AsyncRegionRenderTask[] */
    private array $currentRegionRenders;

    /** @var array{path: string, loader: RegionChunksLoader, mode: int}[] */
    private array $regionChunksLoaders;

    /** @var array{path: string, region: Region}[] */
    private array $regionRenderQueue;

    private int $maxCurrentRenders;
    private int $maxQueueSize;
    private int $maxChunksPerRun;

    public function __construct(PluginBase $plugin)
    {
        $this->plugin = $plugin;
        $this->currentRegionRenders = [];
        $this->regionChunksLoaders = [];
        $this->regionRenderQueue = [];

        $this->maxCurrentRenders = PocketMap::getConfigManger()->getInt("renderer.scheduler.renders", 5);
        $this->maxQueueSize = PocketMap::getConfigManger()->getInt("renderer.scheduler.queue-size", 25);
        $this->maxChunksPerRun = PocketMap::getConfigManger()->getInt("renderer.chunk-loader.chunks-per-run", 128);
    }

    /**
     * Add a region render to teh scheduler
     * @param string $path the path the render is placed in
     * @param Region $region the region to render
     * @param bool $force if the render has to be loaded immediately
     * @return bool if the region is scheduled, if false, you have to manually schedule it again!
     */
    public function scheduleRegionRender(string $path, Region $region, bool $force = false): bool
    {
        // when the action is not forced, don't add the region
        if (!$force && count($this->regionRenderQueue) > $this->maxQueueSize) return false;

        // add the path and region to the queue
        $this->regionRenderQueue[] = [
            "path" => $path,
            "region" => $region
        ];

        return true;
    }

    /**
     * Run the render scheduler.
     * 1. Run the region chunk loaders
     * 2. Run the region renders
     * 3. ONLY WHEN THE QUEUE IS EMPTY: clear the cache
     */
    public function onRun(): void
    {
        // run all the chunk loaders
        $this->runRegionChunksLoaders();
        // run the region renders
        $this->runRegionRenders();
    }

    /**
     * Run the region chunk loaders
     * @return void
     */
    private function runRegionChunksLoaders(): void
    {
        $notCompleted = [];

        foreach ($this->regionChunksLoaders as $rcl) {
            /** @var string $path */
            $path = $rcl["path"];
            /** @var RegionChunksLoader $loader */
            $loader = $rcl["loader"];
            /** @var int $renderMode */
            $renderMode = $rcl["mode"];

            // is completely loaded
            if ($loader->run()) {
                $this->startRenderTask($path, $loader->getRegionChunks(), $renderMode);
                continue;
            }

            // add to the not completed list
            $notCompleted[] = $rcl;
        }

        $this->regionChunksLoaders = $notCompleted;
    }

    /**
     * Start a new async region render
     * @param string $path the path the render will be placed in
     * @param RegionChunks $regionChunks the chunks inside the region
     * @param int $renderMode the render behaviour
     * @return void
     */
    private function startRenderTask(string $path, RegionChunks $regionChunks, int $renderMode): void
    {
        // create a new async task
        $task = new AsyncRegionRenderTask($path, $regionChunks, $renderMode);
        $this->currentRegionRenders[] = $task;

        // submit the task to the async pool
        $this->plugin->getServer()->getAsyncPool()->submitTask($task);
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
            $region = $rr["region"];

            // amount of chunks in render is higher than max load limit
            if (pow($region->getTotalChunks(), 2) > $this->maxChunksPerRun) {
                // now we are going to use a region chunks loader
                $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($region->getWorldName());
                $loader = new RegionChunksLoader($region, $world->getProvider());
                $this->regionChunksLoaders[] = [
                    "path" => $path,
                    "loader" => $loader,
                    "mode" => $region->getRenderMode()
                ];
                continue;
            }

            // get now the chunks of this region
            $regionChunks = RegionChunks::getCompleted($region, $this->plugin->getServer()->getWorldManager());

            $this->startRenderTask($path, $regionChunks, $region->getRenderMode());
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
}