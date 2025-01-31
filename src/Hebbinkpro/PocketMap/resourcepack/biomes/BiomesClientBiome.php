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
 * Copyright (c) 2025 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\resourcepack\biomes;

use Hebbinkpro\PocketMap\utils\WorldUtils;
use pocketmine\world\biome\Biome;

final class BiomesClientBiome
{
    private ?Biome $biome;
    private string $waterSurfaceColor;
    private float $waterSurfaceTransparency;
    private string $fogIdentifier;
    private bool $removeAllPriorFog;
    private bool $inheritFromPriorFog;

    /**
     * @param Biome|null $biome
     * @param string $waterSurfaceColor
     * @param float $waterSurfaceTransparency
     * @param string $fogIdentifier
     * @param bool $removeAllPriorFog
     * @param bool $inheritFromPriorFog
     */
    public function __construct(?Biome $biome, string $waterSurfaceColor, float $waterSurfaceTransparency, string $fogIdentifier, bool $removeAllPriorFog, bool $inheritFromPriorFog)
    {
        $this->biome = $biome;
        $this->waterSurfaceColor = $waterSurfaceColor;
        $this->waterSurfaceTransparency = $waterSurfaceTransparency;
        $this->fogIdentifier = $fogIdentifier;
        $this->removeAllPriorFog = $removeAllPriorFog;
        $this->inheritFromPriorFog = $inheritFromPriorFog;
    }

    public static function parse(string $biomeName, array $data): BiomesClientBiome
    {
        // get the biome, can be null
        $biome = WorldUtils::getBiomeByName($biomeName);

        $waterSurfaceColor = $data["water_surface_color"] ?? "#44AFF5";
        $waterSurfaceTransparency = $data["water_surface_transparency"] ?? 0.65;
        $fogIdentifier = $data["fog_identifier"] ?? "minecraft:fog_default";
        $removeAllPriorFog = $data["remove_all_prior_fog"] ?? false;
        $inheritFromPriorFog = $data["inherit_from_prior_fog"] ?? false;

        return new BiomesClientBiome($biome, $waterSurfaceColor, $waterSurfaceTransparency, $fogIdentifier, $removeAllPriorFog, $inheritFromPriorFog);
    }

    /**
     * @return Biome|null
     */
    public function getBiome(): ?Biome
    {
        return $this->biome;
    }

    /**
     * @return string
     */
    public function getWaterSurfaceColor(): string
    {
        return $this->waterSurfaceColor;
    }

    /**
     * @return float
     */
    public function getWaterSurfaceTransparency(): float
    {
        return $this->waterSurfaceTransparency;
    }

    /**
     * @return string
     */
    public function getFogIdentifier(): string
    {
        return $this->fogIdentifier;
    }

    /**
     * @return bool
     */
    public function isRemoveAllPriorFog(): bool
    {
        return $this->removeAllPriorFog;
    }

    /**
     * @return bool
     */
    public function isInheritFromPriorFog(): bool
    {
        return $this->inheritFromPriorFog;
    }
}