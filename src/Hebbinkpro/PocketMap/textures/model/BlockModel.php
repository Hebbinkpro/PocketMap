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
use Hebbinkpro\PocketMap\textures\model\geometry\ModelGeometry;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

abstract class BlockModel implements BlockModelInterface
{

    /**
     * Get the block model texture from the block texture
     * @param Block $block
     * @param Chunk $chunk
     * @param GdImage $texture
     * @return GdImage|null
     */
    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): ?GdImage
    {

        $modelTexture = TextureUtils::getEmptyTexture();
        if ($modelTexture === false) return null;

        // when there is no valid geometry, the default geometry should be used.
        $geo = $this->getGeometry($block, $chunk);
        if ($geo === null) $geo = self::getDefaultGeometry();

        foreach ($geo as $parts) {
            // new system
            $partModel = $parts->createTexture($texture);
            // copy the resized texture onto the model
            imagealphablending($partModel, true);
            imagecopy($modelTexture, $partModel, 0, 0, 0, 0, 16, 16);
            imagesavealpha($partModel, true);

        }

        return $modelTexture;
    }


    /**
     * Default texture model of the full 16x16 texture
     * @return array|null
     */
    public static function getDefaultGeometry(): ?array
    {
        return [new ModelGeometry()];
    }
}