<?php

namespace Hebbinkpro\PocketMap\utils;

class ArrayUtils
{
    /**
     * Merge multiple arrays recursively together.
     * Unlike the default php array_merge_recursive, this function will use the keys as given in the arrays.
     * If a duplicate key is given and the current value and new value are both arrays, these will be merged.
     * When the values are not types, the value will be overwritten.
     * @param array ...$arrays the arrays to merge together
     * @return array the result of the merge
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