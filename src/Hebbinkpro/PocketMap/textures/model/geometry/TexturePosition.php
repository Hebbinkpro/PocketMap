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
 * Copyright (c) 2024-2025 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\textures\model\geometry;


use Hebbinkpro\PocketMap\PocketMap;

class TexturePosition
{
    public function __construct(private int $x, private int $y)
    {
    }

    /**
     * Get position with x=y=0
     * @return self
     */
    public static function zero(): self
    {
        return new self(0, 0);
    }

    /**
     * Get position with x=y=PocketMap::TEXTURE_SIZE
     * @return self
     */
    public static function max(): self
    {
        return new self(PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE);
    }

    /**
     * Get position with x=PocketMap::TEXTURE_SIZE, y=0
     * @return self
     */
    public static function maxX(): self
    {
        return new self(PocketMap::TEXTURE_SIZE, 0);
    }

    /**
     * Get position with x=0, y=PocketMap::TEXTURE_SIZE
     * @return self
     */
    public static function maxY(): self
    {
        return new self(0, PocketMap::TEXTURE_SIZE);
    }

    /**
     * Get a texture position from an array of two integers
     * @param array{int, int} $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        return new self(intval($array[0]), intval($array[1]));
    }

    /**
     * Get a texture position with the same x and y value
     * @param int $value
     * @return self
     */
    public static function xy(int $value): self
    {
        return new self($value, $value);
    }

    /**
     * Returns a position with 7,7 as the center.
     * There is no real center on a 16x16 pixel image, but this can be used for a generalized center.
     * @return self
     */
    public static function center(): self
    {
        return new self(7, 7);
    }

    /**
     * @return int
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY(): int
    {
        return $this->y;
    }

    /**
     * Alias for $this->getY() to fix inconsistencies in between tile x,z positions
     * @return int
     */
    public function getZ(): int
    {
        return $this->y;
    }
}