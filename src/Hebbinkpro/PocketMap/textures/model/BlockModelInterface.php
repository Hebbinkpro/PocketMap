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
use Hebbinkpro\PocketMap\textures\model\geometry\ModelGeometryInterface;
use pocketmine\block\Block;
use pocketmine\world\format\Chunk;

interface BlockModelInterface
{
    /**
     * Get the block model texture from the block texture
     * @param Block $block
     * @param Chunk $chunk
     * @param GdImage $texture
     * @return GdImage|null
     */
    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): ?GdImage;

    /**
     * Get the block geometry.
     * @param Block $block
     * @param Chunk $chunk
     * @return ModelGeometryInterface[]|null
     */
    public function getGeometry(Block $block, Chunk $chunk): ?array;
}