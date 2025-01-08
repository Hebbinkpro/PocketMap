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

namespace Hebbinkpro\PocketMap\textures\model\flat;

use Hebbinkpro\PocketMap\textures\model\geometry\FlatModelGeometry;
use Hebbinkpro\PocketMap\textures\model\geometry\TexturePosition;
use pocketmine\block\Block;
use pocketmine\block\BrewingStand;
use pocketmine\block\utils\BrewingStandSlot;
use pocketmine\world\format\Chunk;

/**
 * TODO add base texture
 */
class BrewingStandModel extends FlatBlockModel
{

//    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): ?GdImage
//    {
//        if (!$block instanceof BrewingStand) return null;
//
//        $modelTexture = TextureUtils::getEmptyTexture();
//        if ($modelTexture === false) return null;
//
//        $colors = TextureUtils::getTopColors($texture);
//        $size = PocketMap::TEXTURE_SIZE - 1;
//
//        $fullColors = array_slice($colors, 0, 8);
//        $emptyColors = array_reverse(array_slice($colors, 8, 8));
//
//        $fullSlots = array_values($block->getSlots());
//
//
//        imagealphablending($modelTexture, false);
//        foreach (BrewingStandSlot::getAll() as $slot) {
//
//            $colors = $emptyColors;
//            if (in_array($slot, $fullSlots)) $colors = $fullColors;
//
//
//            switch ($slot) {
//                case BrewingStandSlot::EAST:
//                    foreach (array_reverse($colors) as $i => $color) {
//                        imagesetpixel($modelTexture, $i + 8, 7, $color);
//                    }
//                    break;
//                case BrewingStandSlot::NORTHWEST:
//                    foreach ($colors as $i => $color) {
//                        imagesetpixel($modelTexture, $i, $i, $color);
//                    }
//                    break;
//                case BrewingStandSlot::SOUTHWEST:
//                    foreach ($colors as $i => $color) {
//                        imagesetpixel($modelTexture, $i, 15 - $i, $color);
//                    }
//            }
//        }
//        imagesavealpha($modelTexture, true);
//
//        imagedestroy($texture);
//        return $modelTexture;
//    }

    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        if (!$block instanceof BrewingStand) return null;

        // the reversed colors are used to make sure the center of the brewing stand is always in the center
        $full = new FlatModelGeometry(src: 0, srcSize: 8);
        $empty = new FlatModelGeometry(src: 8, srcSize: 8, reverseColors: true);

        $fullSlots = array_values($block->getSlots());

        $geo = [];
        // loop through all brewing stand slots
        foreach (BrewingStandSlot::getAll() as $slot) {
            $part = $empty;
            if (in_array($slot, $fullSlots)) $part = $full;

            switch ($slot) {
                case BrewingStandSlot::EAST:
                    $geo[] = new FlatModelGeometry(
                        src: $part->getSrc(),
                        srcSize: $part->getSrcSize(),
                        dstStart: TexturePosition::center(),
                        dstEnd: new TexturePosition(16, 7),
                        reverseColors: !$part->hasReversedColors()
                    );
                    break;
                case BrewingStandSlot::NORTHWEST:
                    $geo[] = new FlatModelGeometry(
                        src: $part->getSrc(),
                        srcSize: $part->getSrcSize(),
                        dstStart: TexturePosition::zero(),
                        dstEnd: TexturePosition::center(),
                        reverseColors: $part->hasReversedColors()
                    );
                    break;
                case BrewingStandSlot::SOUTHWEST:
                    $geo[] = new FlatModelGeometry(
                        src: $part->getSrc(),
                        srcSize: $part->getSrcSize(),
                        dstStart: TexturePosition::maxY(),
                        dstEnd: TexturePosition::center(),
                        reverseColors: $part->hasReversedColors()
                    );
                    break;
            }
        }

        return $geo;
    }
}