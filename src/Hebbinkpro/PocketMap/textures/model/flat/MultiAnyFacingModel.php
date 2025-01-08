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
use Hebbinkpro\PocketMap\textures\model\BlockModel;
use Hebbinkpro\PocketMap\textures\model\FullBlockModel;
use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use pocketmine\block\Block;
use pocketmine\block\GlowLichen;
use pocketmine\math\Facing;
use pocketmine\world\format\Chunk;

class MultiAnyFacingModel extends FlatBlockModel
{

    /**
     * @param BlockModel $horizontalBlockModel model to be used when facing UP or DOWN
     */
    public function __construct(private BlockModel $horizontalBlockModel = new FullBlockModel())
    {
    }


    public function getModelTexture(Block $block, Chunk $chunk, GdImage $texture): ?GdImage
    {
        $faces = $this->getFaces($block);

        // top and/or down face is in use, only return the full block geometry
        if (sizeof(array_intersect([Facing::DOWN, Facing::UP], $faces)) > 0) {
            // use the full block
            return $this->horizontalBlockModel->getModelTexture($block, $chunk, $texture);
        }

        // it's a vertical model, render the flat block model
        return parent::getModelTexture($block, $chunk, $texture);
    }


    public function getGeometry(Block $block, Chunk $chunk): ?array
    {
        // default geometry is a square
        // facing represent the face of the block it is attached to, so North is actually south
        return self::square(0, $this->getFaces($block), true);
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