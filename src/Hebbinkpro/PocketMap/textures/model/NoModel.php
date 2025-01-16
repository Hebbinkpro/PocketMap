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

namespace Hebbinkpro\PocketMap\textures\model;

use GdImage;
use Hebbinkpro\PocketMap\textures\model\geometry\ModelGeometry;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

/**
 * Simple solution which returns an empty texture
 */
class NoModel implements BlockModelInterface
{

    /**
     * @inheritDoc
     */
    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): ?GdImage
    {
        $modelTexture = TextureUtils::getEmptyTexture();
        if ($modelTexture === false) return null;

        // return the empty texture
        return $modelTexture;
    }

    /**
     * @inheritDoc
     */
    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        return [new ModelGeometry()];
    }
}