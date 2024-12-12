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
 * Copyright (c) 2024 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\textures\model;

use GdImage;
use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\block\GlowLichen;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class MultiAnyFacingModel extends BlockModel
{


    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): ?GdImage
    {
        $faces = $this->getFaces($block);

        // return full block texture
        if (sizeof($faces) == 0) return parent::getModelTexture($block, $chunk, $texture);

        // top and/or down face is in use, only return the full block geometry
        if (sizeof(array_intersect([Facing::DOWN, Facing::UP], $faces)) > 0) {
            return parent::getModelTexture($block, $chunk, $texture);
        }

        // get the top colors of the texture
        $colors = TextureUtils::getTopColors($texture);
        // create empty texture
        $model = TextureUtils::getEmptyTexture();
        $max = sizeof($colors) - 1;

        foreach ($faces as $face) {
            for ($i = 0; $i <= $max; $i++) {
                $color = $colors[$i];
                switch ($face) {
                    case Facing::NORTH:
                        imagesetpixel($model, $i, 0, $color);
                        break;
                    case Facing::EAST:
                        imagesetpixel($model, $max, $i, $color);
                        break;
                    case Facing::SOUTH:
                        imagesetpixel($model, $max - $i, $max, $color);
                        break;
                    case Facing::WEST:
                        imagesetpixel($model, 0, $max - $i, $color);
                }
            }
        }

        return $model;
    }


    public function getGeometry(Block $block, Chunk $chunk): array
    {
        return self::getDefaultGeometry();
    }

    /**
     * Get the faces of the given block
     * @param Block $block
     * @return array
     */
    public function getFaces(Block $block): array
    {
        if (!BlockUtils::hasMultiAnyFacing($block)) return [];
        /** @var GlowLichen $block */
        return $block->getFaces();
    }
}