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

use Hebbinkpro\PocketMap\resourcepack\ResourcePackParser;
use pocketmine\world\biome\Biome;

final class BiomesClientData implements ResourcePackParser
{
    public const DEFAULT_BIOME = "default";

    /** @var array<int, BiomesClientBiome> */
    private array $biomes;

    /**
     * @param array<int, BiomesClientBiome> $biomes
     */
    public function __construct(array $biomes)
    {
        $this->biomes = $biomes;
    }

    /**
     * Parse biome client data from
     * @param array $data
     * @return BiomesClientData
     */
    public static function parse(array $data): BiomesClientData
    {
        if (!isset($data["biomes"])) {
            return new BiomesClientData([]);
        }

        $biomes = [];
        foreach ($data["biomes"] as $name => $biomeData) {
            $biomes[$name] = BiomesClientBiome::parse($name, $biomeData);
        }

        return new BiomesClientData($biomes);
    }

    /**
     * Get biome data by biome
     * @param Biome $biome
     * @return BiomesClientBiome|null
     */
    public function getByBiome(Biome $biome): ?BiomesClientBiome
    {
        return $this->biomes[$biome->getId()] ?? null;
    }

    public function merge(BiomesClientData $data): BiomesClientData
    {
        $biomes = array_merge($this->biomes, $data->getBiomes());
        return new BiomesClientData($biomes);
    }

    /**
     * @return array
     */
    public function getBiomes(): array
    {
        return $this->biomes;
    }
}