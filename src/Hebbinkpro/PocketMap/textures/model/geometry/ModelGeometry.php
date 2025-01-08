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

class ModelGeometry implements ModelGeometryInterface
{
    private TexturePosition $src;
    private TexturePosition $srcSize;
    private TexturePosition $dst;
    private TexturePosition $dstSize;
    private int $rotation;
    private bool $clockwiseRotation;


    public function __construct(TexturePosition $srcStart = null, TexturePosition $srcSize = null, TexturePosition $dstStart = null, TexturePosition $dstSize = null, int $rotation = 0, bool $clockwiseRotation = true)
    {
        $this->src = $srcStart ?? TexturePosition::zero();
        $this->srcSize = $srcSize ?? TexturePosition::max();
        $this->dst = $dstStart ?? $this->src;
        $this->dstSize = $dstSize ?? $this->srcSize;
        $this->rotation = $rotation;
        $this->clockwiseRotation = $clockwiseRotation;
    }

    /**
     * Convert a legacy array geometry to a model geometry
     * @param array $geometry
     * @return ModelGeometry|null
     */
    public static function fromLegacy(array $geometry): ?ModelGeometry
    {

        if (sizeof($geometry) < 2) return null;

        if (sizeof($geometry) == 2) {
            return new self(
                TexturePosition::fromArray($geometry[0]),
                TexturePosition::fromArray($geometry[1])
            );
        }

        // 3rd number is rotation
        if (is_numeric($geometry[2])) {
            return new self(
                TexturePosition::fromArray($geometry[0]),
                TexturePosition::fromArray($geometry[1]),
                rotation: intval($geometry[2])
            );
        }

        return new self(
            TexturePosition::fromArray($geometry[0]),
            TexturePosition::fromArray($geometry[1]),
            isset($geometry[2]) ? TexturePosition::fromArray($geometry[2]) : null,
            isset($geometry[3]) ? TexturePosition::fromArray($geometry[3]) : null,
            isset($geometry[4]) ? intval($geometry[4]) : 0
        );

    }

    /**
     * Get the start coordinate of the src
     * @return TexturePosition
     */
    public function getSrc(): TexturePosition
    {
        return $this->src;
    }

    /**
     * Get the width/height of the src
     * @return TexturePosition
     */
    public function getSrcSize(): TexturePosition
    {
        return $this->srcSize;
    }

    /**
     * Get the start coordinate of the dst
     * @return TexturePosition
     */
    public function getDst(): TexturePosition
    {
        return $this->dst;
    }

    /**
     * Get the width/height of the dst
     * @return TexturePosition
     */
    public function getDstSize(): TexturePosition
    {
        return $this->dstSize;
    }

    /**
     * Get the rotation of the dst that should be applied
     * @return int
     */
    public function getRotation(): int
    {
        return $this->rotation;
    }

    /**
     * Get if the rotation is clockwise
     * @return bool
     */
    public function isRotationClockwise(): bool
    {
        return $this->clockwiseRotation;
    }

    public function createTexture(GdImage $srcImage, int $size = PocketMap::TEXTURE_SIZE): GdImage
    {
        $dstImage = TextureUtils::getEmptyTexture($size);

        // no rotation
        if ($this->rotation == 0) {
            imagealphablending($srcImage, true);
            imagecopyresized(
                $dstImage, $srcImage,
                $this->dst->getX(), $this->dst->getY(),
                $this->src->getX(), $this->src->getY(),
                $this->dstSize->getX(), $this->dstSize->getY(),
                $this->srcSize->getX(), $this->srcSize->getY()
            );
            imagesavealpha($srcImage, true);

            return $dstImage;
        }


        // convert clockwise to anti-clockwise rotation and make sure it is between 0 and 360
        if ($this->clockwiseRotation) $rotation = 360 - $this->rotation;
        else $rotation = $this->rotation;

        $rotation %= 360;

        // create an empty texture on which the resized part is copied
        $tmpImage = TextureUtils::getEmptyTexture();
        imagealphablending($tmpImage, true);
        imagecopyresized(
            $tmpImage, $srcImage,
            $this->dst->getX(), $this->dst->getY(),
            $this->src->getX(), $this->src->getY(),
            $this->dstSize->getX(), $this->dstSize->getY(),
            $this->srcSize->getX(), $this->srcSize->getY()
        );
        imagesavealpha($tmpImage, true);

        // rotate teh texture
        $tmpImage = imagerotate($tmpImage, $rotation, 0);

        # copy the rotated texture onto the model
        imagealphablending($tmpImage, true);
        imagecopy($dstImage, $tmpImage, 0, 0, 0, 0, 16, 16);
        imagesavealpha($tmpImage, true);

        return $dstImage;
    }
}