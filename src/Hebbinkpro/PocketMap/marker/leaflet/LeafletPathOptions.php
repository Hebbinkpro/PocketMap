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

namespace Hebbinkpro\PocketMap\marker\leaflet;

use JsonSerializable;

/**
 * Class containing all options a Leaflet Path and its inheritors can have.
 * All options have been taken from https://leafletjs.com/reference.html#path
 *
 * This also includes Polylines, Polygons and Circles.
 */
class LeafletPathOptions implements JsonSerializable
{

    /** @var bool Whether to draw stroke along the path. Set it to false to disable borders on polygons or circles. */
    public bool|null $stroke;
    /** @var string|null Stroke color */
    public string|null $color;
    /** @var float|null Stroke width in pixels */
    public float|null $weight;
    /** @var float|null Stroke opacity */
    public float|null $opacity;
    /** @var StrokeLinecap|null A string that defines shape to be used at the end of the stroke. */
    public StrokeLinecap|null $lineCap;
    /** @var StrokeLinejoin|null A string that defines shape to be used at the corners of the stroke. */
    public StrokeLinejoin|null $lineJoin;
    /** @var string|null A string that defines the stroke dash pattern. Doesn't work on Canvas-powered layers in some old browsers. */
    public string|null $dashArray;
    /** @var string|null A string that defines the distance into the dash pattern to start the dash. Doesn't work on Canvas-powered layers in some old browsers. */
    public string|null $dashOffset;
    /** @var bool|null Whether to fill the path with color. Set it to false to disable filling on polygons or circles. */
    public bool|null $fill;
    /** @var string|null Fill color. Defaults to the value of the color option */
    public string|null $fillColor;
    /** @var float|null Fill opacity. */
    public float|null $fillOpacity;
    /** @var FillRule|null A string that defines how the inside of a shape is determined. */
    public FillRule|null $fillRule;
    /** @var bool|null When true, a mouse event on this path will trigger the same event on the map (unless L.DomEvent.stopPropagation is used). */
    public bool|null $bubblingMouseEvents;
    /** @var bool If false, the layer will not emit mouse events and will act as a part of the underlying map. */
    public bool|null $interactive;

    /**
     * Construct new leaflet options, All arguments are OPTIONAL.
     * @param bool|null $stroke
     * @param string|null $color
     * @param float|null $weight
     * @param float|null $opacity
     * @param StrokeLinecap|null $lineCap
     * @param StrokeLinejoin|null $lineJoin
     * @param string|null $dashArray
     * @param string|null $dashOffset
     * @param bool|null $fill
     * @param string|null $fillColor
     * @param float|null $fillOpacity
     * @param FillRule|null $fillRule
     * @param bool|null $bubblingMouseEvents
     * @param bool|null $interactive
     */
    public function __construct(bool $stroke = null, string $color = null, float $weight = null, float $opacity = null, StrokeLinecap $lineCap = null, StrokeLinejoin $lineJoin = null, ?string $dashArray = null, ?string $dashOffset = null, bool $fill = null, string $fillColor = null, float $fillOpacity = null, FillRule $fillRule = null, bool $bubblingMouseEvents = null, bool $interactive = null)
    {
        $this->stroke = $stroke;
        $this->color = $color;
        $this->weight = $weight;
        $this->opacity = $opacity;
        $this->lineCap = $lineCap;
        $this->lineJoin = $lineJoin;
        $this->dashArray = $dashArray;
        $this->dashOffset = $dashOffset;
        $this->fill = $fill;
        $this->fillColor = $fillColor;
        $this->fillOpacity = $fillOpacity;
        $this->fillRule = $fillRule;
        $this->bubblingMouseEvents = $bubblingMouseEvents;
        $this->interactive = $interactive;
    }

    /**
     * Construct leaflet options using only the marker color, filled and fill color
     * @param string $color
     * @param bool $fill
     * @param string|null $fillColor
     * @return self
     */
    public static function createSimple(string $color = "red", bool $fill = false, ?string $fillColor = null): self
    {
        return new self(color: $color, fill: $fill, fillColor: $fillColor);
    }

    /**
     * Parse options from the given data
     * @param array $data
     * @return LeafletPathOptions
     */
    public static function parseOptions(array $data): self
    {
        $options = new self();

        foreach ($data as $key => $value) {
            // all keys are the same as the variable names, so we can use class->{key} to assign the given variables
            // variable does not exist
            if (!isset($options->{$key})) continue;

            // set the variable
            $options->{$key} = $value;
        }
        return $options;
    }

    public function jsonSerialize(): array
    {
        $options = [];

        if ($this->stroke !== null) $options["stroke"] = $this->stroke;
        if ($this->color !== null) $options["color"] = $this->color;
        if ($this->weight !== null) $options["weight"] = $this->weight;
        if ($this->opacity !== null) $options["opacity"] = $this->opacity;
        if ($this->lineCap !== null) $options["lineCap"] = $this->lineCap;
        if ($this->lineJoin !== null) $options["lineJoin"] = $this->lineJoin;
        if ($this->dashArray !== null) $options["dashArray"] = $this->dashArray;
        if ($this->dashOffset !== null) $options["dashOffset"] = $this->dashOffset;
        if ($this->fill !== null) $options["fill"] = $this->fill;
        if ($this->fillColor !== null) $options["fillColor"] = $this->fillColor;
        if ($this->fillOpacity !== null) $options["fillOpacity"] = $this->fillOpacity;
        if ($this->fillRule !== null) $options["fillRule"] = $this->fillRule;
        if ($this->bubblingMouseEvents) $options["bubblingMouseEvents"] = $this->bubblingMouseEvents;
        if ($this->interactive !== null) $options["interactive"] = $this->interactive;

        return $options;
    }
}