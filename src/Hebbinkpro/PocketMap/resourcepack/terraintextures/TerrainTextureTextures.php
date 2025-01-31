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

namespace Hebbinkpro\PocketMap\resourcepack\terraintextures;

use Hebbinkpro\PocketMap\resourcepack\blocks\BlocksTextures;
use Hebbinkpro\PocketMap\resourcepack\TexturesData;

class TerrainTextureTextures extends TexturesData
{
    /**
     * Parse the unique textures of a block from the terrain_texture.json data
     * @param string $blockName key of the data
     * @param array{textures: string|string[]} $data value
     * @return BlocksTextures|null
     */
    public static function parse(string $blockName, array $data): ?BlocksTextures
    {

        $dataTextures = $data['textures'] ?? null;

        if (is_string($dataTextures)) {
            // use the value directly
            return new BlocksTextures($blockName, [$dataTextures]);
        }

        if (!is_array($dataTextures)) return null;


        if (isset($dataTextures["path"])) {
            $textures = [$dataTextures["path"]];
        } else if (isset($dataTextures["variations"])) {
            $bestWeight = 0;
            $texture = null;
            // select the variation with the highest weight
            foreach ($dataTextures["variations"] as $variation) {
                $path = $variation["path"] ?? null;
                $weight = $variation["weight"] ?? 0;

                if ($texture === null || $weight > $bestWeight) {
                    $texture = $path;
                    $bestWeight = $weight;
                }
            }
            if ($texture === null) return null;

            $textures = [$texture];
        } else {
            // get all the values
            $textures = array_values($dataTextures);

            for ($i = 0; $i < sizeof($textures); $i++) {
                $texture = $textures[$i];

                // valid texture
                if (is_string($texture)) continue;

                //  still texture contains a path
                if (isset($texture["path"])) {
                    $textures[$i] = $textures[$i]["path"];
                } else {
                    // invalid values
                    var_dump($textures);
                    return null;
                }
            }
        }

        return new BlocksTextures($blockName, $textures);
    }
}