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

use GdImage;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\textures\model\BlockModelInterface;
use Hebbinkpro\PocketMap\textures\model\geometry\FlatModelGeometry;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

/**
 * This is the model used for blocks with a flat texture which is only visible from the sides.
 * Since it is nice to have a representation for this of blocks, the top of the textures will be used to generate models
 */
abstract class FlatBlockModel implements BlockModelInterface
{

    /**
     * @inheritDoc
     */
    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): ?GdImage
    {
        // get the colors
        $colors = TextureUtils::getTopColors($texture, $this->getMaxHeight($block, $chunk));


        $modelTexture = TextureUtils::getEmptyTexture();

        // copy all parts onto the texture
        foreach ($this->getGeometry($block, $chunk) as $part) {
            $partModel = $part->createTextureFromColors($colors);

            # copy the rotated texture onto the model
            imagealphablending($partModel, true);
            imagecopy($modelTexture, $partModel, 0, 0, 0, 0, 16, 16);
            imagesavealpha($partModel, true);
        }

        return $modelTexture;

    }

    /**
     * The height of the block texture
     * @param Block $block
     * @param Chunk $chunk
     * @return int
     */
    protected function getMaxHeight(Block $block, Chunk $chunk): int
    {
        return PocketMap::TEXTURE_SIZE;
    }

    /**
     * @param Block $block
     * @param Chunk $chunk
     * @return array<FlatModelGeometry>
     */
    public abstract function getGeometry(Block $block, Chunk $chunk): array;
}