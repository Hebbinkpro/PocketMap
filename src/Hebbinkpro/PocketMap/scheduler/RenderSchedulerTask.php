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

use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\region\Region;
use Hebbinkpro\PocketMap\region\RegionChunks;
use Hebbinkpro\PocketMap\region\RegionChunksLoader;
use Hebbinkpro\PocketMap\render\AsyncChunkRenderTask;
use Hebbinkpro\PocketMap\render\AsyncRegionRenderTask;
use Hebbinkpro\PocketMap\render\AsyncRenderTask;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\scheduler\info\ChunkLoaderInfo;
use Hebbinkpro\PocketMap\scheduler\info\RenderInfo;
use Logger;
use pocketmine\scheduler\Task;

class RenderSchedulerTask extends Task
{
    /** @var string[] */
    private static array $unfinishedRenders = [];
    private static Logger $logger;
    private PocketMap $plugin;
    /** @var AsyncRenderTask[] */
    private array $runningRenders;
    /** @var ChunkLoaderInfo[] */
    private array $scheduledChunkLoaders;
    /** @var array<string, RenderInfo> */
    private array $scheduledRenders;
    private int $maxRunningRenders;
    private int $maxScheduled;

    public function __construct(PocketMap $plugin)
    {
        $this->plugin = $plugin;
        $this->runningRenders = [];
        $this->scheduledChunkLoaders = [];
        $this->scheduledRenders = [];

        $scheduler = PocketMap::getSettingsManager()->getScheduler();
        $this->maxRunningRenders = $scheduler->getRenders();
        $this->maxScheduled = $scheduler->getQueue();

        self::$logger = $plugin->getLogger();
    }

    /**
     * Mark a region render as finished
     * @param Region $region the region that is finished
     * @return void
     */
    public static function finishRender(Region $region): void
    {
        if (!in_array($region->getName(), self::$unfinishedRenders, true)) return;
        // remove the region from the list
        self::removeCurrentRender($region);

        // only log when a region with max zoom level has finished, otherwise the console is spammed
        if ($region->getZoom() == WorldRenderer::MAX_ZOOM) {
            self::$logger->debug("[Scheduler] Finished render of region: " . $region->getName());
        }

        // start now the render for the next zoom level
        $renderer = PocketMap::getWorldRenderer($region->getWorldName());
        if ($renderer !== null && ($next = $region->getNextZoomRegion()) !== null) {
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
        $key = array_search($region->getName(), self::$unfinishedRenders, true);
        if ($key !== false && is_int($key)) array_splice(self::$unfinishedRenders, $key, 1);
    }

    /**
     * Add a region render to the scheduler
     * @param string $path the path the render is placed in
     * @param Region $region the region to render
     * @param bool $force if the render has to be added no matter how full the queue is
     * @return bool if the region is scheduled, if false, you have to manually schedule it again!
     * @internal
     */
    public function scheduleRegionRender(string $path, Region $region, bool $replace = false, bool $force = false): bool
    {
        // check if the region is already scheduled
        $scheduled = array_key_exists($region->getName(), $this->scheduledRenders);

        // when the action is not forced, and it isn't yet scheduled
        if (!$force && !$scheduled && sizeof($this->scheduledRenders) >= $this->maxScheduled) return false;

        // this render is already scheduled
        if (($replace || !$force) && $scheduled) {
            // and we are not allowed to replace
            if (!$replace) return false;

            // we are going to replace the already existing render
            // remove the render that was already queued
            unset($this->scheduledRenders[$region->getName()]);
            // remove the region from the current renders
            self::removeCurrentRender($region);
        }

        self::$unfinishedRenders[] = $region->getName();

        // add the path and region to the queue
        $this->scheduledRenders[$region->getName()] = new RenderInfo($path, $region);

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
        foreach (array_keys($this->scheduledChunkLoaders) as $key) {
            $chunkLoader = $this->scheduledChunkLoaders[$key];
            $path = $chunkLoader->getPath();
            $loader = $chunkLoader->getLoader();

            // is not completely loaded
            if (!$loader->run()) continue;

            $regionChunks = $loader->getRegionChunks();
            $this->startChunkRenderTask($regionChunks, $path);

            // remove loader from the list
            unset($this->scheduledChunkLoaders[$key]);
        }
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
        $this->runningRenders[] = $task;

        // submit the task to the async pool
        $this->plugin->getServer()->getAsyncPool()->submitTask($task);

        // only log it for the region with the max zoom, otherwise the console is spammed
        if ($region->getZoom() == WorldRenderer::MAX_ZOOM) {
            self::$logger->debug("[Scheduler] Started render of region: " . $region->getName());
        }
    }

    /**
     * Manager of all running region render.
     * - Removes finished renders
     * - Starts new renders when possible
     * @return void
     */
    private function runRegionRenders(): void
    {
        // check if the current renders are completed
        foreach (array_keys($this->runningRenders) as $i) {
            $render = $this->runningRenders[$i];

            // render is not finished
            if (!$render->isFinished()) continue;

            // remove the finished render
            unset($this->runningRenders[$i]);
        }

        $wm = $this->plugin->getServer()->getWorldManager();
        // add new renders until the cap is reached or no new renders are available
        while ($this->getCurrentRendersCount() < $this->maxRunningRenders && sizeof($this->scheduledRenders) > 0) {
            $render = array_shift($this->scheduledRenders);
            $path = $render->getPath();
            $region = $render->getRegion();

            // if all chunks should be rendered, prepare for the ChunkRenderTask
            if ($region->renderAllChunks()) {
                $worldName = $region->getWorldName();
                // the world does not exist (is not loaded and cannot be loaded)
                if ($this->plugin->getLoadedWorld($worldName)) continue;

                $world = $wm->getWorldByName($worldName);
                if ($world === null) continue;

                $loader = new RegionChunksLoader($region, $world->getProvider());
                $this->scheduledChunkLoaders[] = new ChunkLoaderInfo($path, $loader, 0);

                continue;
            }

            $this->startRegionRenderTask($region, $path);
        }
    }

    /**
     * Get the number of renders that are currently running
     * @return int
     */
    public function getCurrentRendersCount(): int
    {
        return sizeof($this->runningRenders) + sizeof($this->scheduledChunkLoaders);
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