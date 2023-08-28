<?php

namespace Hebbinkpro\PocketMap\task;

use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\render\PartialRegion;
use Hebbinkpro\PocketMap\render\Region;
use Hebbinkpro\PocketMap\render\RegionChunks;
use Hebbinkpro\PocketMap\render\RegionChunksLoader;
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

        self::$logger = $plugin->getLogger();
    }

    public static function finishedRender(Region $region): void
    {
        if (!in_array("$region", self::$currentRenders)) return;
        // remove the region from the list
        $key = array_search("$region", self::$currentRenders);
        array_splice(self::$currentRenders, $key, 1);

        self::$logger->debug("[Scheduler] Finished render of region: $region");
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
        // when the action is not forced or the region is already in the scheduler, don't add the region
        if ((!$force && count($this->regionRenderQueue) >= $this->maxQueueSize) ||
            in_array("$region", self::$currentRenders)) return false;

        self::$currentRenders[] = "$region";

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
     */
    public function onRun(): void
    {
        var_dump("[DEBUG 1] current:".count(self::$currentRenders).", count:".$this->getCurrentRendersCount().", queue:".count($this->regionRenderQueue));

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
                $chunks = $loader->getRegionChunks();
                $region = $chunks->getRegion();

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

                $this->startRenderTask($path, $chunks, $renderMode);
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

        self::$logger->debug("[Scheduler] Started render of region: " . $regionChunks->getRegion());
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