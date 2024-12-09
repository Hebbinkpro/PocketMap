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

namespace Hebbinkpro\PocketMap\extension;

use Hebbinkpro\DebugWorld\DebugWorld;
use Hebbinkpro\DebugWorld\DebugWorldGenerator;
use Hebbinkpro\PocketMap\marker\CircleMarker;
use Hebbinkpro\PocketMap\marker\leaflet\LeafletPathOptions;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class DebugWorldExtension extends BaseExtension implements Listener
{
    private DebugWorld $debugWorld;


    /**
     * @inheritDoc
     */
    public static function getRequiredPlugins(): array
    {
        return ["DebugWorld"];
    }

    /**
     * @inheritDoc
     */
    public static function getRequiredClasses(): array
    {
        return [
            DebugWorldExtension::class,
            DebugWorldGenerator::class,
        ];
    }

    public function onDebugWorldLoad(WorldLoadEvent $event): void
    {
        $world = $event->getWorld();

        // no debug world, or world already has debug markers
        if (!$this->debugWorld->isDebugWorld($world) || $this->hasDebugWorldMarkers($world)) return;

        // load all chunks of the debug world grid
        // this will make PocketMap render the complete grid without having to join the server
        $this->loadGridChunks($world);

        // register the debug world markers
        $this->addDebugWorldMarkers($world);

    }

    private function hasDebugWorldMarkers(World $world): bool
    {
        $markers = PocketMap::getMarkers();
        return $markers->isMarker($world, "debug_0");
    }

    /**
     * Get the size of the debug world grid
     * @return int
     */
    public static function getDebugWorldGridSize(): int
    {
        $states = sizeof(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates());

        // get the grid size
        return (int)ceil(sqrt($states));
    }

    private function addDebugWorldMarkers(World $world): void
    {
        $this->getPlugin()->getLogger()->debug("[Extension] [DebugWorld] Adding markers to debug world '{$world->getFolderName()}'");

        $markers = PocketMap::getMarkers();
        // filled marker which is invisible
        $markerOptions = new LeafletPathOptions(opacity: 0, fill: true, fillOpacity: 0);

        $blocks = array_values(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates());
        $size = self::getDebugWorldGridSize();

        foreach ($blocks as $i => $block) {
            $state = $block->getStateId();
            $name = $block->getName();

            // get x,z coordinates from the block index
            $x = $i % $size;
            $z = (int)floor($i / $size);
            // create the position
            // multiply x,z by 2 since the grid is at even coordinates, 0,2,4,6,...
            $pos = new Vector3($x * 2, DebugWorldGenerator::GRID_HEIGHT, $z * 2);
            $radius = 0.5;

            // create circle marker since this only requires 1 position which is more efficient
            $marker = new CircleMarker("debug_" . $state, $name . "-" . $state, $pos, $radius, $markerOptions);

            // don't store the markers directly
            $markers->addMarker($world, $marker, false);
        }

        // store the makers when we are finished
        $markers->storeMarkers();
    }

    /**
     * Load all chunks of the debug world grid
     * @param World $world
     * @return void
     */
    private function loadGridChunks(World $world): void
    {
        $size = self::getDebugWorldGridSize();

        $plugin = $this->getPlugin();
        // divide by 8 since there are 8x8 blocks inside a debug grid chunk
        for ($x = 0; $x <= ceil($size / 8); $x++) {
            for ($z = 0; $z <= ceil($size / 8); $z++) {
                if ($world->loadChunk($x, $z) !== null) continue;

                // generate the chunk
                $world->orderChunkPopulation($x, $z, null)->onCompletion(
                    static function () use ($plugin, $x, $z, $world): void {
                        $plugin->getLogger()->debug("Generated chunk $x,$z of world '{$world->getFolderName()}'");
                    },
                    static function (): void {
                        //NOOP: we don't care if the world was unloaded
                    });
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function onEnable(): void
    {
        $debugWorld = $this->getPlugin("DebugWorld");
        if (!$debugWorld instanceof DebugWorld) return;
        $this->debugWorld = $debugWorld;

        $this->registerEvents($this);


    }
}