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
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

final class DefaultBlockModel extends BlockModel
{
    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): GdImage
    {
        // check if the texture is 16x16 and not 32x32 or larger
        // this is used to automatically downscale larger textures, like the education edition textures
        $size = imagesx($texture);
        if ($size != PocketMap::TEXTURE_SIZE) {
            $resized = TextureUtils::getEmptyTexture();
            if ($resized === false) return $texture;

            # copy the texture onto the model
            imagealphablending($texture, true);
            imagecopyresized($resized, $texture, 0, 0, 0, 0, PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE, $size, $size);
            imagesavealpha($texture, true);

            return $resized;
        }

        return $texture;
    }

    public function getGeometry(Block $block, Chunk $chunk): array
    {
        return [
            [
                [0, 0],
                [PocketMap::TEXTURE_SIZE, PocketMap::TEXTURE_SIZE]
            ]
        ];
    }
}