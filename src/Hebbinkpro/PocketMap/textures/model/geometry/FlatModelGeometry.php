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

use GdImage;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\utils\TextureUtils;

class FlatModelGeometry implements ModelGeometryInterface
{
    private int $src;
    private int $srcSize;
    private TexturePosition $dstStart;
    private TexturePosition $dstEnd;
    private int $rotation;
    private bool $clockwiseRotation;
    private bool $reverseColors;

    /**
     * @param int $src
     * @param int $srcSize
     * @param TexturePosition|null $dstStart
     * @param TexturePosition|null $dstEnd
     * @param int $rotation
     * @param bool $clockwiseRotation
     * @param bool $reverseColors
     */
    public function __construct(int $src = 0, int $srcSize = PocketMap::TEXTURE_SIZE, TexturePosition $dstStart = null, TexturePosition $dstEnd = null, int $rotation = 0, bool $clockwiseRotation = true, bool $reverseColors = false)
    {
        $this->src = $src;
        $this->srcSize = $srcSize;
        $this->dstStart = $dstStart ?? TexturePosition::zero();
        $this->dstEnd = $dstEnd ?? TexturePosition::maxX();
        $this->rotation = $rotation;
        $this->clockwiseRotation = $clockwiseRotation;
        $this->reverseColors = $reverseColors;
    }

    /**
     * Create a texture using the provided src image
     * @param GdImage $srcImage
     * @param int $size
     * @return GdImage
     */
    public function createTexture(GdImage $srcImage, int $size = PocketMap::TEXTURE_SIZE): GdImage
    {
        // flat model, so get the highest pixels on the src image
        $colors = TextureUtils::getTopColors($srcImage);

        return $this->createTextureFromColors($colors, $size);
    }

    /**
     * Create a texture using the provided color array
     * @param array $colors
     * @param int $size
     * @return GdImage
     */
    public function createTextureFromColors(array $colors, int $size = PocketMap::TEXTURE_SIZE): GdImage
    {
        // get the colors used for this texture
        $colors = array_slice($colors, $this->src, $this->srcSize);

        // reverse the colors in the array
        if ($this->reverseColors) $colors = array_reverse($colors);

        $dstImage = $this->drawLineWithColors($colors, $size);

        if ($this->rotation == 0) return $dstImage;

        // convert clockwise to anti-clockwise rotation and make sure it is between 0 and 360
        if ($this->clockwiseRotation) $rotation = 360 - $this->rotation;
        else $rotation = $this->rotation;

        $rotation %= 360;

        imagesavealpha($dstImage, true);
        return imagerotate($dstImage, $rotation, 0);
    }

    /**
     * Draw a line of colors on an image
     * @param array $colors
     * @param int $size
     * @return GdImage
     */
    private function drawLineWithColors(array $colors, int $size): GdImage
    {
        $x0 = $this->dstStart->getX();
        $y0 = $this->dstStart->getY();
        $x1 = $this->dstEnd->getX();
        $y1 = $this->dstEnd->getY();

        $dx = abs($x1 - $x0);
        $dy = abs($y1 - $y0);

        $sx = ($x0 < $x1) ? 1 : -1;
        $sy = ($y0 < $y1) ? 1 : -1;

        $err = $dx - $dy;

        $linePixels = [];

        // Generate all pixels on the line using Bresenham's algorithm
        while (true) {
            $linePixels[] = [$x0, $y0];
            if ($x0 === $x1 && $y0 === $y1) break;
            $e2 = 2 * $err;
            if ($e2 > -$dy) {
                $err -= $dy;
                $x0 += $sx;
            }
            if ($e2 < $dx) {
                $err += $dx;
                $y0 += $sy;
            }
        }

        // Adjust the pixel count to match sizeof($colors)
        $n = sizeof($colors);
        $totalPixels = count($linePixels);
        if ($n < $totalPixels) {
            // Downsample to $n pixels
            $step = $totalPixels / $n;
            $selectedPixels = [];
            for ($i = 0; $i < $n; $i++) {
                $selectedPixels[] = $linePixels[(int)round($i * $step)];
            }
        } elseif ($n > $totalPixels) {
            // Duplicate some pixels to match $n
            $selectedPixels = $linePixels;
            while (count($selectedPixels) < $n) {
                $selectedPixels = array_merge($selectedPixels, $linePixels);
            }
            $selectedPixels = array_slice($selectedPixels, 0, $n);
        } else {
            $selectedPixels = $linePixels;
        }

        // Draw the line with the specified colors
        $colorCount = count($colors);
        $image = TextureUtils::getEmptyTexture($size);
        for ($i = 0; $i < $n; $i++) {
            [$x, $y] = $selectedPixels[$i];
            $color = $colors[$i % $colorCount]; // Cycle through colors if fewer colors than pixels
            imagesetpixel($image, $x, $y, $color);
        }

        return $image;
    }

    /**
     * @return int
     */
    public function getSrc(): int
    {
        return $this->src;
    }

    /**
     * @return int
     */
    public function getSrcSize(): int
    {
        return $this->srcSize;
    }

    /**
     * @return TexturePosition
     */
    public function getDstStart(): TexturePosition
    {
        return $this->dstStart;
    }

    /**
     * @return TexturePosition
     */
    public function getDstEnd(): TexturePosition
    {
        return $this->dstEnd;
    }

    /**
     * @return int
     */
    public function getRotation(): int
    {
        return $this->rotation;
    }

    /**
     * @return bool
     */
    public function hasClockwiseRotation(): bool
    {
        return $this->clockwiseRotation;
    }

    public function hasReversedColors(): bool
    {
        return $this->reverseColors;
    }
}