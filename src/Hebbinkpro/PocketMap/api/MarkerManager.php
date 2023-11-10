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

namespace Hebbinkpro\PocketMap\api;

use pocketmine\world\Position;
use pocketmine\world\World;

class MarkerManager
{

    private string $folder;
    private array $markers;
    private array $icons;

    public function __construct(string $folder)
    {
        if (!str_ends_with($folder, "/")) $folder .= "/";
        $this->folder = $folder;

        $this->markers = [];
        if (is_file($this->folder."markers.json")) {
            $file = file_get_contents($this->folder."markers.json");
            $this->markers = json_decode($file, true) ?? [];
        }

        $this->icons = [];
        if (is_file($this->folder."icons.json")) {
            $file = file_get_contents($this->folder."icons.json");
            $icons = json_decode($file, true) ?? [];

            foreach ($icons as $i) {
                $this->icons[] = $i["name"];
            }
        }
    }

    /**
     * @return array
     */
    public function getIcons(): array
    {
        return $this->icons;
    }

    public function addMarker(string $name, string $icon, Position $pos, ?string $index = null): bool {
        if (!in_array($icon, $this->icons)) return false;

        [$x,$z] = [$pos->getFloorX(), $pos->getFloorZ()];
        if ($this->getMarkerIndex($pos) != null ||
            ($index != null && $this->getMarker($index, $pos->getWorld()) != null)) {
            return false;
        }

        $world = $pos->getWorld()->getFolderName();

        $index = $index ?? count($this->markers[$world]);
        $marker = [
            "id" => $index,
            "name" => $name,
            "icon" => $icon,
            "pos" => [
                "x" => $x,
                "z" => $z
            ]
        ];

        if (!isset($this->markers[$world])) $this->markers[$world] = [];

        $this->markers[$world][$index] = $marker;
        $this->encode();

        return true;
    }

    /**
     * Remove a marker
     * @param string|int $index the markers index
     * @param World $world the world the marker is in
     * @return bool
     */
    public function removeMarker(string|int $index, World $world): bool {
        if ( $this->getMarker($index, $world) == null) return false;

        unset($this->markers[$world][$index]);
        $this->encode();

        return true;
    }

    /**
     * Get a marker by its position
     * @param Position $pos the position of the marker
     * @return string|int|null the index in the markers[world] list, or null if it doesn't exist
     */
    public function getMarkerIndex(Position $pos): null|string|int {
        $world = $pos->getWorld()->getFolderName();
        foreach ($this->markers[$world] ?? [] as $i=>$marker) {
            if ($marker["pos"]["x"] == $pos->getFloorX() && $marker["pos"]["z"] == $pos->getFloorZ()) {
                return $i;
            }
        }

        return null;
    }

    public function getMarker(string|int $index, World $world): ?array {
        if (!isset($this->markers[$world->getFolderName()])) return null;

        return $this->markers[$world->getFolderName()][$index] ?? null;
    }

    /**
     * Store the markers as json
     * @return void
     */
    private function encode(): void {
        $data = json_encode($this->markers);
        file_put_contents($this->folder."markers.json", $data);
    }
}