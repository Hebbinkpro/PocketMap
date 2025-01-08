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

namespace Hebbinkpro\PocketMap\textures\model;

use GdImage;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\block\BrewingStand;
use pocketmine\block\utils\BrewingStandSlot;
use pocketmine\world\format\Chunk;

/**
 * TODO add base texture
 */
class BrewingStandModel extends CrossModel
{

    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): ?GdImage
    {
        if (!$block instanceof BrewingStand) return null;

        $modelTexture = TextureUtils::getEmptyTexture();
        if ($modelTexture === false) return null;

        $colors = TextureUtils::getTopColors($texture);
        $size = PocketMap::TEXTURE_SIZE - 1;

        $fullColors = array_slice($colors, 0, 8);
        $emptyColors = array_reverse(array_slice($colors, 8, 8));

        $fullSlots = array_values($block->getSlots());


        imagealphablending($modelTexture, false);
        foreach (BrewingStandSlot::getAll() as $slot) {

            $colors = $emptyColors;
            if (in_array($slot, $fullSlots)) $colors = $fullColors;


            switch ($slot) {
                case BrewingStandSlot::EAST:
                    foreach (array_reverse($colors) as $i => $color) {
                        imagesetpixel($modelTexture, $i + 8, 7, $color);
                    }
                    break;
                case BrewingStandSlot::NORTHWEST:
                    foreach ($colors as $i => $color) {
                        imagesetpixel($modelTexture, $i, $i, $color);
                    }
                    break;
                case BrewingStandSlot::SOUTHWEST:
                    foreach ($colors as $i => $color) {
                        imagesetpixel($modelTexture, $i, 15 - $i, $color);
                    }
            }
        }
        imagesavealpha($modelTexture, true);

        imagedestroy($texture);
        return $modelTexture;
    }

}