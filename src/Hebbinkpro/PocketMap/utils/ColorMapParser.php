<?php

namespace Hebbinkpro\PocketMap\utils;

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

    private static array $colorMap = [];

    public static function getColorFromBlock(Block $block, Biome $biome, ResourcePack $rp): int
    {
        if (!array_key_exists($rp->getPath(), self::$colorMap)) {
            self::$colorMap[$rp->getPath()] = [];
        }
        if (!array_key_exists($block->getTypeId(), self::$colorMap[$rp->getPath()])) {
            self::$colorMap[$rp->getPath()][$block->getTypeId()] = [];
        }

        // get the color from the cache, or request the color
        $color = self::$colorMap[$rp->getPath()][$block->getTypeId()][$biome->getId()] ?? match ($block->getTypeId()) {
            Ids::WATER => self::getWaterColor($biome, $rp),
            Ids::SPRUCE_LEAVES => self::getColorFromMapFromBiome($biome, $rp->getPath() . self::COLOR_MAP_EVERGREEN),
            Ids::BIRCH_LEAVES => self::getColorFromMapFromBiome($biome, $rp->getPath() . self::COLOR_MAP_BIRCH),
            Ids::GRASS, Ids::TALL_GRASS, Ids::DOUBLE_TALLGRASS, Ids::FERN, Ids::LARGE_FERN, Ids::SUGARCANE => self::getGrassColor($biome, $rp),
            Ids::OAK_LEAVES, Ids::JUNGLE_LEAVES, Ids::ACACIA_LEAVES, Ids::DARK_OAK_LEAVES, Ids::VINES => self::getFoliageColor($biome, $rp),
            default => -1,
        };

        // if the biome color does not exist, add it to the cache
        if (!array_key_exists($biome->getId(), self::$colorMap[$rp->getPath()][$block->getTypeId()])) {
            self::$colorMap[$rp->getPath()][$block->getTypeId()][$biome->getId()] = $color;
        }

        return $color;
    }

    public static function getWaterColor(Biome $biome, ResourcePack $rp): int
    {
        $biomes = json_decode(file_get_contents($rp->getPath() . self::BIOMES_CLIENT), true)["biomes"];
        $biomeData = $biomes[self::getBiomeName($biome)] ?? $biomes["default"];

        $color = $biomeData["water_surface_color"];
        return hexdec(str_replace("#", "", $color));
    }

    public static function getBiomeName(Biome $biome): string
    {
        $reflector = new ReflectionClass(BiomeIds::class);

        return strtolower(array_search($biome->getId(), $reflector->getConstants()) ?? "ocean");
    }

    public static function getColorFromMapFromBiome(Biome $biome, string $texture): int
    {
        return self::getColorFromMap($biome->getTemperature(), $biome->getRainfall(), $texture);
    }

    public static function getColorFromMap(float $temp, float $rain, string $texture): int
    {
        // make the value between 0 and 1 if it isn't already
        $temp = min(1, max(0, $temp));
        $rain = min(1, max(0, $rain));

        $img = imagecreatefrompng($texture);
        imagealphablending($img, true);

        $x = floor(self::COLOR_MAP_SIZE - ($temp * self::COLOR_MAP_SIZE));
        $y = floor(self::COLOR_MAP_SIZE - ($rain * self::COLOR_MAP_SIZE * $temp));

        if ($x < 0 || $y < 0 || $x > self::COLOR_MAP_SIZE || $y > self::COLOR_MAP_SIZE) {
            $color = imagecolorexactalpha($img, 0, 0, 0, 127);
        } else $color = imagecolorat($img, $x, $y);

        // if no color was found, create a transparent pixel
        if (!$color)


            imagedestroy($img);

        return $color;
    }

    public static function getGrassColor(Biome $biome, ResourcePack $rp): int
    {
        $texture = $rp->getPath() . self::COLOR_MAP_GRASS;
        $temp = $biome->getTemperature();
        $rain = $biome->getRainfall();

        switch ($biome->getId()) {
            case BiomeIds::SWAMPLAND:
                $texture = $rp->getPath() . self::COLOR_MAP_GRASS_SWAMP;
                break;

            case BiomeIds::MESA:
            case BiomeIds::MESA_PLATEAU:
            case BiomeIds::MESA_BRYCE:
            case BiomeIds::MESA_PLATEAU_MUTATED:
            case BiomeIds::MESA_PLATEAU_STONE:
            case BiomeIds::MESA_PLATEAU_STONE_MUTATED:
                return 0x90814D;

            case BiomeIds::ROOFED_FOREST:
            case BiomeIds::ROOFED_FOREST_MUTATED:
                $color = self::getColorFromMap($temp, $rain, $texture) & 0xFEFEFE;
                return self::average($color, 0x28340A);

            // TODO: mangrove swamp
        }

        return self::getColorFromMap($temp, $rain, $texture);
    }

    public static function average(int $color1, int $color2): int
    {
        $placeholder = imagecreatetruecolor(1, 1);
        $c1 = imagecolorsforindex($placeholder, $color1);
        $c2 = imagecolorsforindex($placeholder, $color2);

        $cr = [
            "red" => sqrt(($c1["red"] ^ 2 + $c2["red"] ^ 2) / 2),
            "green" => sqrt(($c1["green"] ^ 2 + $c2["green"] ^ 2) / 2),
            "blue" => sqrt(($c1["blue"] ^ 2 + $c2["blue"] ^ 2) / 2),
            "alpha" => sqrt(($c1["alpha"] ^ 2 + $c2["alpha"] ^ 2) / 2)
        ];

        $average = imagecolorexactalpha($placeholder, $cr["red"], $cr["green"], $cr["blue"], $cr["alpha"]);
        imagedestroy($placeholder);
        return $average;
    }

    public static function getFoliageColor(Biome $biome, ResourcePack $rp): int
    {
        $texture = $rp->getPath() . self::COLOR_MAP_FOLIAGE;
        $temp = $biome->getTemperature();
        $rain = $biome->getRainfall();

        switch ($biome->getId()) {
            case BiomeIds::SWAMPLAND:
                $texture = $rp->getPath() . self::COLOR_MAP_FOLIAGE_SWAMP;
                break;

            case BiomeIds::MESA:
            case BiomeIds::MESA_PLATEAU:
            case BiomeIds::MESA_BRYCE:
            case BiomeIds::MESA_PLATEAU_MUTATED:
            case BiomeIds::MESA_PLATEAU_STONE:
            case BiomeIds::MESA_PLATEAU_STONE_MUTATED:
                return 0x9E814D;

            case BiomeIds::ROOFED_FOREST:
            case BiomeIds::ROOFED_FOREST_MUTATED:
                $color = self::getColorFromMap($temp, $rain, $texture) & 0xFEFEFE;
                return self::average($color, 0x28340A);

            // TODO: mangrove swamp
        }

        return self::getColorFromMap($temp, $rain, $texture);
    }

    public static function clearCache(): void
    {

        // clear cache of all resource packs
        $rps = array_keys(self::$colorMap);
        foreach ($rps as $rp) {
            unset(self::$colorMap[$rp]);
        }
    }
}