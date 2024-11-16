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

namespace Hebbinkpro\PocketMap\marker;

use Hebbinkpro\PocketMap\marker\leaflet\LeafletPathOptions;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\world\World;
use Ramsey\Uuid\Uuid;

class MarkerManager
{

    private PocketMap $plugin;
    /** @var array<string, array<string, BaseMarker>> {worldName: {markerId: BaseMarker}} */
    private array $markers = [];
    /** @var string[] List of all available icon names */
    private array $icons = [];

    public function __construct(PocketMap $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Get a new unique marker ID
     * @return string
     */
    public static function getMarkerId(): string
    {
        // return a new UUID string
        return Uuid::uuid4()->toString();
    }

    /**
     * Load all marker data
     * @return void
     */
    public function load(): void
    {
        $this->loadIcons();
        $this->loadMarkers();
    }

    /**
     * Load all the available icons
     * @return void
     */
    public function loadIcons(): void
    {
        $iconsFile = $this->plugin->getMarkersFolder() . "icons/icons.json";


        // assume that the icons folder exists
        $icons = json_decode(file_get_contents($iconsFile), true);

        $this->icons = [];
        foreach ($icons as $icon) {
            if (!isset($icon["name"])) continue;
            $this->icons[] = $icons["name"];
        }

    }

    /**
     * Load all the stored markers
     * @return void
     */
    public function loadMarkers(): void
    {
        $markersFile = $this->plugin->getMarkersFolder() . "markers.json";

        $this->markers = [];
        if (!is_file($markersFile)) {
            // file does not exist, so create a new empty file
            file_put_contents($markersFile, json_encode([]));
            // nothing to load, so return
            return;
        }

        $data = json_decode(file_get_contents($markersFile));
        foreach ($data as $worldName => $markers) {
            $this->markers[$worldName] = [];
            foreach ($markers as $id => $markerData) {
                $marker = $this->parseMarker($id, $markerData);
                if ($marker === null) continue;
                $this->markers[$worldName][$id] = $marker;
            }
        }
    }

    /**
     * Parse a marker from its given ID and data
     * @param string $id the marker ID
     * @param array $data the marker data
     * @return BaseMarker|null a marker of null
     */
    public function parseMarker(string $id, array $data): ?BaseMarker
    {
        // invalid data
        if (!isset($data["name"]) || !isset($data["type"])) return null;

        // get the type
        $type = MarkerType::tryFrom($data["type"]);
        // check if the type is valid
        if ($type === null) return null;

        $name = $data["name"];

        // switch over all available marker types
        switch ($type) {
            case MarkerType::ICON:
                // invalid keys
                if (!isset($data["position"]) || !isset($data["icon"])) return null;

                // parse position, return if it is invalid
                $pos = PositionMarker::parsePosition($data["position"]);
                if ($pos === null) return null;

                // invalid icon
                $icon = $data["icon"];
                if (!$this->isIcon($icon)) return null;

                return new IconMarker($id, $name, $pos, $icon);

            case MarkerType::CIRCLE:
                if (!isset($data["position"]) || !isset($data["options"])) return null;

                // parse position, return if it is invalid
                $pos = PositionMarker::parsePosition($data["position"]);
                if ($pos === null) return null;

                if (!isset($data["options"]["radius"])) return null;
                $radius = intval($data["options"]["radius"]);
                $options = LeafletPathOptions::parseOptions($data["options"]);

                return new CircleMarker($id, $name, $pos, $radius, $options);

            case MarkerType::POLYLINE:
                if (!isset($data["positions"]) || !isset($data["options"])) return null;
                $positions = PositionsMarker::parsePositions($data["positions"]);
                if ($positions === null) return null;

                $options = LeafletPathOptions::parseOptions($data["options"]);
                return new PolylineMarker($id, $name, $positions, $options);

            case MarkerType::POLYGON:
                if (!isset($data["positions"]) || !isset($data["options"])) return null;
                $positions = PositionsMarker::parsePositions($data["positions"]);
                if ($positions === null) return null;

                $options = LeafletPathOptions::parseOptions($data["options"]);
                return new PolygonMarker($id, $name, $positions, $options);
        }

        return null;
    }

    /**
     * Get if the given icon exists
     * @param string $icon the icon to check
     * @return bool if the icon exists
     */
    public function isIcon(string $icon): bool
    {
        return in_array($icon, $this->icons);
    }

    /**
     * Add or update a marker in the given world
     * @param World $world the world of the marker
     * @param BaseMarker $marker the marker
     * @param bool $store if the markers should be stored directly after adding the marker
     */
    public function addMarker(World $world, BaseMarker $marker, bool $store = true): void
    {
        $worldName = $world->getFolderName();
        if (!isset($this->markers[$worldName])) $this->markers[$worldName] = [];

        // set the marker
        $this->markers[$world->getFolderName()][$marker->getId()] = $marker;

        // store the markers
        if ($store) $this->storeMarkers();
    }

    /**
     * Get a marker by its world and id
     * @param World $world
     * @param string $id
     * @return BaseMarker|null
     */
    public function getMarker(World $world, string $id): ?BaseMarker
    {
        if (!isset($this->markers[$world->getFolderName()][$id])) return null;
        return $this->markers[$world->getFolderName()][$id];
    }

    /**
     * Check if there exists a marker in the world with the given id
     * @param World $world
     * @param string $id
     * @return bool
     */
    public function isMarker(World $world, string $id): bool
    {
        return isset($this->markers[$world->getFolderName()][$id]);
    }

    /**
     * Store the markers in a file
     * @return void
     */
    public function storeMarkers(): void
    {

        // serialize all the markers
        $contents = [];
        foreach ($this->markers as $worldName => $markers) {
            $contents[$worldName] = [];
            foreach ($markers as $id => $marker) {
                $contents[$worldName][$id] = $marker->jsonSerialize();
            }
        }

        // store the markers in the file
        $markersFile = $this->plugin->getMarkersFolder() . "markers.json";
        file_put_contents($markersFile, json_encode($contents));
    }

    /**
     * Remove a marker from the world
     * @param World $world the world of the marker
     * @param string $id
     * @param bool $store
     * @return void
     */
    public function removeMarker(World $world, string $id, bool $store = true): void
    {
        unset($this->markers[$world->getFolderName()][$id]);

        if ($store) $this->storeMarkers();
    }

    /**
     * Get all the available icons
     * @return string[]
     */
    public function getIcons(): array
    {
        return $this->icons;
    }
}