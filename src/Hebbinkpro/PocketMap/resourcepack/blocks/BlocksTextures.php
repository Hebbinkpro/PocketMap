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

namespace Hebbinkpro\PocketMap\resourcepack\blocks;

use Hebbinkpro\PocketMap\resourcepack\TexturesData;

class BlocksTextures extends TexturesData
{

    /**
     * Parse the unique textures of a block from the blocks.json data
     * @param string $blockName key of the data
     * @param array{textures: string|string[]} $data value
     * @return BlocksTextures|null
     */
    public static function parse(string $blockName, array $data): ?BlocksTextures
    {

        $dataTextures = $data['textures'] ?? null;

        if (is_string($dataTextures)) {
            // use the value directly
            $textures = [$dataTextures];
        } elseif (is_array($dataTextures)) {
            // get all the unique values of the array
            $textures = array_unique(array_values($dataTextures));
        } else {
            // unexpected data type
            return null;
        }

        return new BlocksTextures($blockName, $textures);
    }
}