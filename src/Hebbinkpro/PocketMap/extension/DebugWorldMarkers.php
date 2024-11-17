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
use Hebbinkpro\PocketMap\marker\PolygonMarker;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class DebugWorldMarkers extends BaseExtension implements Listener
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
            DebugWorld::class,
            DebugWorldGenerator::class,
        ];
    }

    public function onDebugWorldLoad(WorldLoadEvent $event): void
    {
        $world = $event->getWorld();

        // no debug world, or world already has debug markers
        if (!$this->debugWorld->isDebugWorld($world) || $this->hasDebugWorldMarkers($world)) return;

        // register the debug world markers
        $this->registerDebugWorldMarkers($world);
    }

    private function hasDebugWorldMarkers(World $world): bool
    {
        $markers = PocketMap::getMarkers();
        $airState = VanillaBlocks::AIR()->getStateId();
        return $markers->isMarker($world, "debug_$airState");
    }

    private function registerDebugWorldMarkers(World $world): void
    {
        $markers = PocketMap::getMarkers();
        // filled marker which is invisible
        $markerOptions = new LeafletPathOptions(opacity: 0., fill: true, fillOpacity: 0.);

        $blocks = array_values(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates());

        // get the grid size
        $states = sizeof($blocks);
        $size = (int)ceil(sqrt($states));

        $square = [
            new Vector3(0, 0, 0),
            new Vector3($size * 2, 0, 0),
            new Vector3($size * 2, 0, $size * 2),
            new Vector3(0, 0, $size * 2),
        ];
        $markers->addMarker($world, new PolygonMarker("debug_grid", "Block Grid", $square, new LeafletPathOptions(fill: false, fillOpacity: 0)));

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