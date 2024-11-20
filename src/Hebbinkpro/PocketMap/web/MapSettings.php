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

namespace Hebbinkpro\PocketMap\web;

use JsonSerializable;

class MapSettings implements JsonSerializable
{
    private int $minZoom;
    private int $maxZoom;

    private int $viewX;
    private int $viewY;
    private int $viewZoom;

    /**
     * @param int $minZoom
     * @param int $maxZoom
     * @param int $viewX
     * @param int $viewY
     * @param int $viewZoom
     */
    public function __construct(int $minZoom, int $maxZoom, int $viewX, int $viewY, int $viewZoom)
    {
        $this->minZoom = $minZoom;
        $this->maxZoom = $maxZoom;
        $this->viewX = $viewX;
        $this->viewY = $viewY;
        $this->viewZoom = $viewZoom;
    }

    public static function fromArray(array $data): self
    {
        $zoom = $data["zoom"] ?? [0, 8];
        $view = $data["view"] ?? [256, 256, 4];

        $minZoom = intval($zoom[0] ?? 0);
        $maxZoom = intval($zoom[1] ?? 8);
        $viewX = intval($view[0] ?? 256);
        $viewY = intval($view[1] ?? 256);
        $viewZoom = intval($view[2] ?? 4);

        return new self($minZoom, $maxZoom, $viewX, $viewY, $viewZoom);
    }

    public function jsonSerialize(): array
    {
        return [
            "zoom" => [$this->minZoom, $this->maxZoom],
            "view" => [$this->viewX, $this->viewY, $this->viewZoom],
        ];
    }
}