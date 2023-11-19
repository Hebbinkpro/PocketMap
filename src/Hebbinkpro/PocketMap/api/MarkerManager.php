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
use pocketmine\world\Position;
use pocketmine\world\World;

class MarkerManager
{
    public const TYPE_ICON = "icon";
    public const TYPE_CIRCLE = "circle";
    public const TYPE_POLYGON = "polygon";
    public const TYPE_POLYLINE = "polyline";

    private string $folder;
    /** @var array<string, array<array{name: string, data: array{type: string}}>> */
    private array $markers;
    /** @var array<string> */
    private array $icons;

    public function __construct(string $folder)
    {
        if (!str_ends_with($folder, "/")) $folder .= "/";
        $this->folder = $folder;

        $this->markers = [];
        if (!is_file($this->folder . "markers.json")) {
            file_put_contents($this->folder . "markers.json", "[]");
        }

        $this->update();

        $this->icons = [];
        if (is_file($this->folder . "icons.json")) {
            $file = file_get_contents($this->folder . "icons.json");


            if ($file === false) $icons = [];
            else $icons = json_decode($file, true) ?? [];

            if ($icons === false) $icons = [];


            /** @var array<array{name: string, path: string}> $icons */
            foreach ($icons as $i) {
                $this->icons[] = $i["name"];
            }
        }
    }

    /**
     * Load all markers from the file
     * @return void
     */
    private function update(): void
    {
        $contents = file_get_contents($this->folder . "markers.json");
        if ($contents === false) return;

        /** @var null|array<string, array<array{name: string, data: array{type: string}}>> $markers */
        $markers = json_decode($contents, true);
        if ($markers !== null) $this->markers = $markers;
    }

    /**
     * @return array<string>
     */
    public function getIcons(): array
    {
        return $this->icons;
    }

    /**
     * Mark a position with an icon
     * @param string $name
     * @param Position $pos
     * @param string $icon
     * @param string|null $id
     * @return bool
     */
    public function addIconMarker(string $name, Position $pos, string $icon, ?string $id = null): bool
    {
        if (!$this->isIcon($icon)) return false;

        $data = [
            "type" => self::TYPE_ICON,
            "icon" => $icon
        ];

        return $this->addPositionMarker($name, $data, $pos, $id);
    }

    public function isIcon(string $name): bool {
        return in_array($name, $this->icons, true);
    }

    /**
     * @param string $name
     * @param array{type: string} $data
     * @param Position $pos
     * @param string|null $id
     * @return bool
     */
    protected function addPositionMarker(string $name, array $data, Position $pos, ?string $id = null): bool
    {
        $data["pos"] = [
            "x" => $pos->getX(),
            "z" => $pos->getZ()
        ];

        return $this->addMarker($name, $data, $pos->getWorld(), $id);
    }

    /**
     * @param string $name
     * @param array{type?: string} $data
     * @param World $world
     * @param string|null $id
     * @return bool
     */
    protected function addMarker(string $name, array $data, World $world, ?string $id = null): bool
    {
        $this->update();
        if (!isset($data["type"])) return false;

        $worldName = $world->getFolderName();

        // id is already used
        if ($id !== null && $this->getMarker($id, $world) !== null) return false;
        else {
            $lower = str_replace(" ", "_", strtolower($name));
            $id = $lower;
            $loop = 1;
            while ($this->getMarker($id, $world) !== null) {
                $id = $lower . $loop;
                $loop ++;
            }
        }

        $marker = [
            "name" => $name,
            "data" => $data,
        ];

        if (!isset($this->markers[$worldName])) $this->markers[$worldName] = [];

        $this->markers[$worldName][$id] = $marker;
        $this->encode();

        return true;
    }

    /**
     * @param string $id
     * @param World $world
     * @return array{name: string, data: array{type: string}}|null
     */
    public function getMarker(string $id, World $world): ?array
    {
        if (!isset($this->markers[$world->getFolderName()])) return null;

        return $this->markers[$world->getFolderName()][$id] ?? null;
    }

    /**
     * Store the markers as json
     * @return void
     */
    private function encode(): void
    {
        $data = json_encode($this->markers, JSON_PRETTY_PRINT);
        file_put_contents($this->folder . "markers.json", $data);
    }

    /**
     * Place a circle over a position
     * @param string $name
     * @param Position $pos
     * @param int $radius
     * @param string $color
     * @param bool $fill
     * @param string|null $fillColor
     * @param string|null $id
     * @return bool
     */
    public function addCircleMarker(string $name, Position $pos, int $radius, ?string $id = null, string $color = "red", bool $fill = false, ?string $fillColor = null): bool
    {

        $options = $this->getLeafletOptions($color, $fill, $fillColor);
        $options["radius"] = $radius;

        $data = [
            "type" => self::TYPE_CIRCLE,
            "options" => $options
        ];

        return $this->addPositionMarker($name, $data, $pos, $id);
    }

    /**
     * @param string $color
     * @param bool $fill
     * @param string|null $fillColor
     * @return array{color: string, fill: bool, fillColor: string}
     */
    protected function getLeafletOptions(string $color, bool $fill, ?string $fillColor = null): array
    {
        return [
            "color" => $color,
            "fill" => $fill,
            "fillColor" => $fillColor ?? $color
        ];
    }

    /**
     * Mark an area
     * @param string $name
     * @param Vector3[] $positions
     * @param World $world
     * @param string $color
     * @param bool $fill
     * @param string|null $fillColor
     * @param string|null $id
     * @return bool
     */
    public function addPolygonMarker(string $name, array $positions, World $world, ?string $id = null, string $color = "red", bool $fill = false, ?string $fillColor = null): bool
    {
        $data = [
            "type" => self::TYPE_POLYGON,
            "options" => $this->getLeafletOptions($color, $fill, $fillColor)
        ];

        return $this->addMultiPositionMarker($name, $data, $positions, $world, $id);
    }

    /**
     * @param string $name
     * @param array{type: string} $data
     * @param Vector3[] $positions
     * @param World $world
     * @param string|null $id
     * @return bool
     */
    protected function addMultiPositionMarker(string $name, array $data, array $positions, World $world, ?string $id = null): bool
    {
        // add all positions
        $data["positions"] = [];
        foreach ($positions as $pos) {
            $data["positions"][] = [
                "x" => $pos->getX(),
                "z" => $pos->getZ()
            ];
        }

        return $this->addMarker($name, $data, $world, $id);
    }

    /**
     * Create a multipoint line
     * @param string $name
     * @param Vector3[] $positions
     * @param World $world
     * @param string $color
     * @param bool $fill
     * @param string|null $fillColor
     * @param string|null $id
     * @return bool
     */
    public function addPolylineMarker(string $name, array $positions, World $world, ?string $id = null, string $color = "red", bool $fill = false, ?string $fillColor = null): bool
    {
        $data = [
            "type" => self::TYPE_POLYLINE,
            "options" => $this->getLeafletOptions($color, $fill, $fillColor)
        ];

        return $this->addMultiPositionMarker($name, $data, $positions, $world, $id);
    }

    /**
     * Remove a marker
     * @param string $id the markers index
     * @param World $world the world the marker is in
     * @return bool
     */
    public function removeMarker(string $id, World $world): bool
    {
        $this->update();
        if ($this->getMarker($id, $world) === null) return false;

        unset($this->markers[$world->getFolderName()][$id]);
        $this->encode();

        return true;
    }
}