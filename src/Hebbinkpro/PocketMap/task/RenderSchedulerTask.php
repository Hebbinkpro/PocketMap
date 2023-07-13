<?php

namespace Hebbinkpro\PocketMap\task;

use Hebbinkpro\PocketMap\render\Region;
use Hebbinkpro\PocketMap\render\RegionChunks;
use Hebbinkpro\PocketMap\render\RegionChunksLoader;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\utils\ColorMapParser;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;

class RenderSchedulerTask extends Task
{
    /**
     * Max amount of renders running simultaneously
     */
    public const MAX_CURRENT_RENDERS = 5;

    private PluginBase $plugin;

    /** @var array<Region, AsyncRegionRenderTask> */
    private array $currentRegionRenders;

    /** @var array{0: string, 1: RegionChunksLoader}[] */
    private array $regionChunksLoaders;

    /** @var array{0: string, 1: Region}[] */
    private array $regionRenderQueue;

    /**
     * List containing all worlds including all their zoom levels that have to be rendered.
     * @var array{renderer: WorldRenderer, levels: int[]}[]
     */
    private array $fullWorldRenderQueue;

    public function __construct(PluginBase $plugin)
    {
        $this->plugin = $plugin;
        $this->currentRegionRenders = [];
        $this->regionChunksLoaders = [];
        $this->regionRenderQueue = [];
        $this->fullWorldRenderQueue = [];
    }

    public function scheduleRegionRender(string $path, Region $region, bool $force = false): bool
    {
        // when the action is not forced, don't add the region
        if (!$force && count($this->regionRenderQueue) > self::MAX_CURRENT_RENDERS * 5) return false;
        $this->regionRenderQueue[] = [$path, $region];

        return true;
    }

    public function scheduleFullWorldRender(WorldRenderer $worldRenderer, array $zoomLevels = [])
    {
        if (empty($zoomLevels)) $zoomLevels = array_keys(WorldRenderer::ZOOM_LEVELS);

        $this->plugin->getLogger()->notice("Starting full world render of world: " . $worldRenderer->getWorld()->getFolderName());
        $this->plugin->getLogger()->warning("It is possible to notice a drop in server performance during a full world render.");

        $this->fullWorldRenderQueue[$worldRenderer->getWorld()->getFolderName()] = [
            "renderer" => $worldRenderer,
            "levels" => $zoomLevels
        ];
    }

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        $this->runRegionChunksLoaders();
        $this->runRegionRenders();
        $this->runFullWorldRenders();

        // clear the texture cache if the queue is empty
        if (empty($this->currentRegionRenders)) {
            $this->clearCache();
        }
    }

    private function runRegionChunksLoaders(): void
    {
        $notCompleted = [];

        /**
         * @var string $path
         * @var RegionChunksLoader $loader
         * @var int $renderMode
         */
        foreach ($this->regionChunksLoaders as [$path, $loader, $renderMode]) {
            // is completely loaded
            if ($loader->run()) {
                $this->startRenderTask($path, $loader->getRegionChunks(), $renderMode);
                continue;
            }

            // add to the not completed list
            $notCompleted[] = [$path, $loader, $renderMode];
        }

        $this->regionChunksLoaders = $notCompleted;
    }

    private function startRenderTask(string $path, RegionChunks $regionChunks, int $renderMode)
    {
        // create a new async task
        $task = new AsyncRegionRenderTask($path, $regionChunks, $renderMode);
        $this->currentRegionRenders[] = $task;

        // submit the task to the async pool
        $this->plugin->getServer()->getAsyncPool()->submitTask($task);
    }

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
        while ($this->getCurrentRendersCount() < self::MAX_CURRENT_RENDERS && count($this->regionRenderQueue) > 0) {

            /**
             * @var string $path
             * @var Region $region
             */
            [$path, $region] = array_shift($this->regionRenderQueue);

            // amount of chunks in render is higher than max load limit
            if (pow($region->getTotalChunks(), 2) > RegionChunksLoader::MAX_CHUNKS_PER_RUN) {
                // now we are going to use a region chunks loader
                $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($region->getWorldName());
                $loader = new RegionChunksLoader($region, $world->getProvider());
                $this->regionChunksLoaders[] = [$path, $loader, $region->getRenderMode()];
                continue;
            }

            // get now the chunks of this region
            $regionChunks = RegionChunks::getCompleted($region, $this->plugin->getServer()->getWorldManager());

            $this->startRenderTask($path, $regionChunks, $region->getRenderMode());
        }
    }

    public function getCurrentRendersCount(): int
    {
        return count($this->currentRegionRenders) + count($this->regionChunksLoaders);
    }

    private function runFullWorldRenders(): void
    {
        // no worlds to render, or the queue is full
        if (empty($this->fullWorldRenderQueue) || count($this->regionRenderQueue) > self::MAX_CURRENT_RENDERS) return;

        /** @var string $worldName */
        $worldName = array_key_first($this->fullWorldRenderQueue);
        /** @var WorldRenderer $renderer */
        $renderer = $this->fullWorldRenderQueue[$worldName]["renderer"];
        /** @var int $zoomLevel */
        $zoomLevel = $this->fullWorldRenderQueue[$worldName]["levels"][0];

        $this->plugin->getLogger()->debug("Starting full world render of world: $worldName, with zoom: $zoomLevel");

        // start the render for the current zoom level
        $renderer->startZoomRender($zoomLevel);
        array_shift($this->fullWorldRenderQueue[$worldName]["levels"]);

        // all zoom levels are rendered
        if (empty($this->fullWorldRenderQueue[$worldName]["levels"])) {
            unset($this->fullWorldRenderQueue[$worldName]);
        }
    }

    public function clearCache(): void
    {
        ColorMapParser::clearCache();
        TextureUtils::clearCache();
    }

    /**
     * Get a list of all worlds that are currently rendering
     * @return string[]
     */
    public function getFullWorldRenders(): array
    {
        return array_keys($this->fullWorldRenderQueue);
    }
}