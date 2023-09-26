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
 * Copyright (C) 2023 Hebbinkpro
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
use Logger;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;

class RenderSchedulerTask extends Task
{
    /** @var string[] */
    private static array $unfinishedRenders = [];
    private static Logger $logger;
    private PluginBase $plugin;
    /** @var AsyncRegionRenderTask[] */
    private array $runningRenders;
    /** @var array{path: string, loader: RegionChunksLoader, mode: int}[] */
    private array $scheduledChunkLoaders;
    /** @var array<string, array{path: string, region: Region}>[] */
    private array $scheduledRenders;
    private int $maxRunningRenders;
    private int $maxScheduled;

    public function __construct(PluginBase $plugin)
    {
        $this->plugin = $plugin;
        $this->runningRenders = [];
        $this->scheduledChunkLoaders = [];
        $this->scheduledRenders = [];

        $this->maxRunningRenders = PocketMap::getConfigManger()->getInt("renderer.scheduler.renders", 5);
        $this->maxScheduled = PocketMap::getConfigManger()->getInt("renderer.scheduler.queue-size", 25);

        self::$logger = $plugin->getLogger();
    }

    /**
     * Mark a region render as finished
     * @param Region $region the region that is finished
     * @return void
     */
    public static function finishRender(Region $region): void
    {
        if (!in_array($region->getName(), self::$unfinishedRenders)) return;
        // remove the region from the list
        self::removeCurrentRender($region);

        self::$logger->debug("[Scheduler] Finished render of region: " . $region->getName());

        // start now the render for the next zoom level
        $renderer = PocketMap::getWorldRenderer($region->getWorldName());
        if (($next = $region->getNextZoomRegion()) !== null) {
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
        $key = array_search($region->getName(), self::$unfinishedRenders);
        if ($key !== false) array_splice(self::$unfinishedRenders, $key, 1);
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
        $scheduled = in_array($region->getName(), $this->scheduledRenders);

        // when the action is not forced, and it isn't already scheduled
        if (!$force && !$scheduled && count($this->scheduledRenders) >= $this->maxScheduled) return false;

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
        $this->scheduledRenders[$region->getName()] = [
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

        foreach ($this->scheduledChunkLoaders as $rcl) {
            /** @var string $path */
            $path = $rcl["path"];
            /** @var RegionChunksLoader $loader */
            $loader = $rcl["loader"];

            // is completely loaded
            if ($loader->run()) {
                $regionChunks = $loader->getRegionChunks();
                $this->startChunkRenderTask($regionChunks, $path);
                continue;
            }

            // add to the not completed list
            $notCompleted[] = $rcl;
        }

        // clear the list and set the contents to the notCompleted regions.
        unset($this->scheduledChunkLoaders);
        $this->scheduledChunkLoaders = $notCompleted;
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
        foreach ($this->runningRenders as $i => $render) {
            // render has ended
            if ($render->isFinished()) {
                $completed[] = $i;
            }
        }

        // remove all completed renders
        foreach ($completed as $i) {
            unset($this->runningRenders[$i]);
        }

        // add new renders until the cap is reached or no new renders are available
        while ($this->getCurrentRendersCount() < $this->maxRunningRenders && count($this->scheduledRenders) > 0) {
            $rr = array_shift($this->scheduledRenders);
            $path = $rr["path"];
            /** @var Region $region */
            $region = $rr["region"];

            // if all chunks should be rendered, prepare for the ChunkRenderTask
            if ($region->renderAllChunks()) {
                $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($region->getWorldName());
                $loader = new RegionChunksLoader($region, $world->getProvider());
                $this->scheduledChunkLoaders[] = [
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
        return count($this->runningRenders) + count($this->scheduledChunkLoaders);
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