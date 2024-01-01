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

namespace Hebbinkpro\PocketMap\utils;

use Hebbinkpro\PocketMap\textures\TerrainTextures;
use Hebbinkpro\PocketMap\utils\biome\NewBiomeIds;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds as Ids;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\biome\Biome;
use ReflectionClass;

final class ColorMapParser
{
    public const BIOMES_CLIENT = "biomes_client.json";

    public const COLOR_MAP_SIZE = 255;

    public const COLOR_MAP_BIRCH = "textures/colormap/birch.png";
    public const COLOR_MAP_EVERGREEN = "textures/colormap/evergreen.png";
    public const COLOR_MAP_FOLIAGE = "textures/colormap/foliage.png";
    public const COLOR_MAP_GRASS = "textures/colormap/grass.png";
    public const COLOR_MAP_FOLIAGE_MANGROVE_SWAMP = "textures/colormap/mangrove_swamp_foliage.png";
    public const COLOR_MAP_FOLIAGE_SWAMP = "textures/colormap/swamp_foliage.png";
    public const COLOR_MAP_GRASS_SWAMP = "textures/colormap/swamp_grass.png";

    /**
     * @var array<string, array<int, array<int, int>>>
     */
    private static array $colorMap = [];

    /**
     * Get the color of the given block in the Biome using the resource pack
     * @param Block $block the block to get the color of
     * @param Biome $biome the biome the block is in
     * @param TerrainTextures $terrainTextures the resource pack to use
     * @return int the color of the block, or -1 when it is an invalid block
     */
    public static function getColorFromBlock(Block $block, Biome $biome, TerrainTextures $terrainTextures): int
    {
        if (!array_key_exists($terrainTextures->getPath(), self::$colorMap)) {
            self::$colorMap[$terrainTextures->getPath()] = [];
        }
        if (!array_key_exists($block->getTypeId(), self::$colorMap[$terrainTextures->getPath()])) {
            self::$colorMap[$terrainTextures->getPath()][$block->getTypeId()] = [];
        }

        // get the color from the cache, or request the color
        $color = self::$colorMap[$terrainTextures->getPath()][$block->getTypeId()][$biome->getId()] ?? match ($block->getTypeId()) {
            Ids::WATER => self::getWaterColor($biome, $terrainTextures),
            Ids::SPRUCE_LEAVES => self::getColorFromMapFromBiome($biome, $terrainTextures->getRealTexturePath(self::COLOR_MAP_EVERGREEN)),
            Ids::BIRCH_LEAVES => self::getColorFromMapFromBiome($biome, $terrainTextures->getRealTexturePath(self::COLOR_MAP_BIRCH)),
            Ids::GRASS, Ids::TALL_GRASS, Ids::DOUBLE_TALLGRASS, Ids::FERN, Ids::LARGE_FERN, Ids::SUGARCANE => self::getGrassColor($biome, $terrainTextures),
            Ids::OAK_LEAVES, Ids::JUNGLE_LEAVES, Ids::ACACIA_LEAVES, Ids::DARK_OAK_LEAVES, Ids::VINES => self::getFoliageColor($biome, $terrainTextures),
            default => -1,
        };

        // if the biome color does not exist, add it to the cache
        if (!array_key_exists($biome->getId(), self::$colorMap[$terrainTextures->getPath()][$block->getTypeId()])) {
            self::$colorMap[$terrainTextures->getPath()][$block->getTypeId()][$biome->getId()] = $color;
        }

        return $color;
    }

    /**
     * Get the water color in a given biome using the resource pack
     * @param Biome $biome the biome
     * @param TerrainTextures $terrainTextures the resource pack
     * @return int the color of the water
     */
    public static function getWaterColor(Biome $biome, TerrainTextures $terrainTextures): int
    {
        /** @var array{water_surface_color?: string} $biomeData */
        $biomeData = self::getBiomeData($biome, $terrainTextures);
        $hexColor = $biomeData["water_surface_color"] ?? "#44AFF5";
        return (int)hexdec(str_replace("#", "", $hexColor));
    }

    /**
     * Get the biome data from biomes_client.json
     * @param Biome $biome
     * @param TerrainTextures $terrainTextures
     * @return array<string, mixed>
     */
    public static function getBiomeData(Biome $biome, TerrainTextures $terrainTextures): array
    {
        $contents = file_get_contents($terrainTextures->getVanillaPath() . self::BIOMES_CLIENT);
        if ($contents === false) return [];

        /** @var array{biomes: array<string, array<string, mixed>>} $data */
        $data = json_decode($contents, true) ?? ["biomes" => []];
        return $data["biomes"][self::getBiomeName($biome)] ?? $data["biomes"]["default"] ?? [];
    }

    /**
     * Get the id name, the name used in resource packs and BiomeIds, of the given biome
     * @param Biome $biome the biome
     * @return string the id name of the biome
     */
    public static function getBiomeName(Biome $biome): string
    {
        // get the reflector classes
        $reflector = new ReflectionClass(BiomeIds::class);
        $reflectorNew = new ReflectionClass(NewBiomeIds::class);

        // get all constants
        /** @var array<string, int> $constants */
        $constants = array_merge($reflector->getConstants(), $reflectorNew->getConstants());

        // get the name of the current id
        $name = array_search($biome->getId(), $constants, true);
        return $name === false ? "ocean" : strtolower($name);
    }

    /**
     * Get the color from the color map using a biome
     * @param Biome $biome the biome
     * @param string $texture the path of the color map
     * @return int the color
     */
    public static function getColorFromMapFromBiome(Biome $biome, string $texture): int
    {
        return self::getColorFromMap($biome->getTemperature(), $biome->getRainfall(), $texture);
    }

    /**
     * Get the color from the color map
     * @param float $temp the temperature
     * @param float $rain the rainfall
     * @param string $texture the path of the color map
     * @return int the color
     */
    public static function getColorFromMap(float $temp, float $rain, string $texture): int
    {
        // make the value between 0 and 1 if it isn't already
        $temp = self::clamp($temp, 0.0, 1.0);
        $rain = self::clamp($rain, 0.0, 1.0) * $temp;

        $img = imagecreatefrompng($texture);
        if ($img === false) return 0;
        imagealphablending($img, true);

        $x = (int)floor(self::COLOR_MAP_SIZE - ($temp * self::COLOR_MAP_SIZE));
        $y = (int)floor(self::COLOR_MAP_SIZE - ($rain * self::COLOR_MAP_SIZE));

        if ($x < 0 || $y < 0 || $x > self::COLOR_MAP_SIZE || $y > self::COLOR_MAP_SIZE) {
            $color = imagecolorexactalpha($img, 0, 0, 0, 127);
        } else $color = imagecolorat($img, $x, $y);

        // if no color was found, create a transparent pixel
        if ($color === false) {
            imagedestroy($img);
            return 0;
        }

        return $color;
    }

    /**
     * Get the value inside a range
     * @param float|int $value the value
     * @param float|int $min the minimum value
     * @param float|int $max the maximum value
     * @return float the value when inside the range, otherwise min or max
     */
    public static function clamp(float|int $value, float|int $min, float|int $max): float
    {
        return max($min, min($value, $max));
    }

    /**
     * Get the grass color in the given biome
     * @param Biome $biome the biome
     * @param TerrainTextures $terrainTextures the resource pack
     * @return int the grass color
     */
    public static function getGrassColor(Biome $biome, TerrainTextures $terrainTextures): int
    {
        $texture = $terrainTextures->getRealTexturePath(self::COLOR_MAP_GRASS);
        $temp = $biome->getTemperature();
        $rain = $biome->getRainfall();

        return match ($biome->getId()) {
            BiomeIds::SWAMPLAND => 0x6A7039,
            BiomeIds::MESA, BiomeIds::MESA_PLATEAU, BiomeIds::MESA_BRYCE, BiomeIds::MESA_PLATEAU_MUTATED, BiomeIds::MESA_PLATEAU_STONE, BiomeIds::MESA_PLATEAU_STONE_MUTATED => 0x90814D,
            BiomeIds::ROOFED_FOREST, BiomeIds::ROOFED_FOREST_MUTATED => 0x507A32,
            NewBiomeIds::MANGROVE_SWAMP => 0x4C763C,
            default => self::getColorFromMap($temp, $rain, $texture),
        };

    }

    /**
     * Get the foliage color inside a biome
     * @param Biome $biome the biome
     * @param TerrainTextures $terrainTextures the resource pack
     * @return int the foliage color
     */
    public static function getFoliageColor(Biome $biome, TerrainTextures $terrainTextures): int
    {
        $texture = $terrainTextures->getRealTexturePath(self::COLOR_MAP_FOLIAGE);
        $temp = $biome->getTemperature();
        $rain = $biome->getRainfall();

        return match ($biome->getId()) {
            BiomeIds::SWAMPLAND => 0x6A7039,
            BiomeIds::MESA, BiomeIds::MESA_PLATEAU, BiomeIds::MESA_BRYCE, BiomeIds::MESA_PLATEAU_MUTATED, BiomeIds::MESA_PLATEAU_STONE, BiomeIds::MESA_PLATEAU_STONE_MUTATED => 0x9E814D,
            BiomeIds::ROOFED_FOREST, BiomeIds::ROOFED_FOREST_MUTATED => 0x507A32,
            NewBiomeIds::MANGROVE_SWAMP => 0x8DB127,
            NewBiomeIds::CHERRY_GROVE => 0xB6DB61,
            default => self::getColorFromMap($temp, $rain, $texture),
        };

    }

    /**
     * Get the average between two colors
     * @param int $color1 the first color
     * @param int $color2 the second color
     * @return int the average color
     * @deprecated this function isn't working like intended
     */
    public static function average(int $color1, int $color2): int
    {
        $placeholder = imagecreatetruecolor(1, 1);
        if ($placeholder === false) return 0;

        $c1 = imagecolorsforindex($placeholder, $color1);
        $c2 = imagecolorsforindex($placeholder, $color2);

        $cr = [
            "red" => (int)sqrt(($c1["red"] ^ 2 + $c2["red"] ^ 2) / 2),
            "green" => (int)sqrt(($c1["green"] ^ 2 + $c2["green"] ^ 2) / 2),
            "blue" => (int)sqrt(($c1["blue"] ^ 2 + $c2["blue"] ^ 2) / 2),
            "alpha" => (int)sqrt(($c1["alpha"] ^ 2 + $c2["alpha"] ^ 2) / 2)
        ];

        $average = imagecolorexactalpha($placeholder, $cr["red"], $cr["green"], $cr["blue"], $cr["alpha"]);
        imagedestroy($placeholder);
        return $average;
    }

    /**
     * Get the water transparency in a given biome
     * @param Biome $biome
     * @param TerrainTextures $terrainTextures
     * @return float
     */
    public static function getWaterTransparency(Biome $biome, TerrainTextures $terrainTextures): float
    {
        /** @var array{water_surface_transparency?: float} $biomeData */
        $biomeData = self::getBiomeData($biome, $terrainTextures);
        return $biomeData["water_surface_transparency"] ?? 0.650;
    }

    /**
     * Clear the color map cache
     * @return void
     */
    public static function clearCache(): void
    {
        // clear cache of all resource packs
        $textures = array_keys(self::$colorMap);
        foreach ($textures as $path) {
            unset(self::$colorMap[$path]);
        }
    }
}