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

use pocketmine\math\Vector3;

class MarkerManager
{

    private string $folder;
    private array $markers;
    private array $icons;

    public function __construct(string $folder)
    {
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

    public function addMarker(string $name, string $icon, Vector3 $pos): bool {
        if (!in_array($icon, $this->icons)) return false;

        [$x,$z] = [$pos->getFloorX(), $pos->getFloorZ()];
        if ($this->getMarker($x, $z) != null) return false;

        $marker = [
            "name" => $name,
            "icon" => $icon,
            "pos" => [
                "x" => $x,
                "z" => $z
            ]
        ];

        $this->markers[] = $marker;
        $this->encode();

        return true;
    }

    public function removeMarker(Vector3 $pos): bool {
        $index = $this->getMarker($pos->getFloorX(), $pos->getFloorZ());
        if ($index == null) return false;

        array_splice($this->markers, $index, 1);
        $this->encode();

        return true;
    }


    public function getMarker(int $x, int $z): ?int {
        foreach ($this->markers as $i=>$marker) {
            if ($marker["pos"]["x"] == $x && $marker["pos"]["z"] == $z) {
                return $i;
            }
        }

        return null;
    }


    private function encode(): void {
        $data = json_encode($this->markers);
        file_put_contents($this->folder."markers.json", $data);
    }
}