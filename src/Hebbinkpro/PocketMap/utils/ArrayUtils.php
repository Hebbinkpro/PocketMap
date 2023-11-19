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

class ArrayUtils
{
    /**
     * Merge multiple arrays recursively together.
     * Unlike the default php array_merge_recursive, this function will use the keys as given in the arrays.
     * If a duplicate key is given and the current value and new value are both arrays, these will be merged.
     * When the values are not types, the value will be overwritten.
     * @param array<mixed> ...$arrays the arrays to merge together
     * @return array<mixed> the result of the merge
     */
    public static function merge(array ...$arrays): array
    {

        $result = [];
        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                // key does not yet exist
                if (!array_key_exists($key, $result)) {
                    // set the value
                    $result[$key] = $value;
                    continue;
                }

                // the result value and new value are both arrays
                if (is_array($value) && is_array($result[$key])) {
                    // set the value to a merge between the two arrays
                    $result[$key] = self::merge($result[$key], $value);
                }
            }
        }

        return $result;
    }
}