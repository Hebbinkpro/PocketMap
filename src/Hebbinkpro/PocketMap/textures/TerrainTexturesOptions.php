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
 * Copyright (C) 2023 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\textures;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;

class TerrainTexturesOptions
{

    private ?Block $fallbackBlock;
    private int $heightColor;
    private int $heightAlpha;

    public function __construct(?Block $fallbackBlock = null, int $heightColor = 0x000000, int $heightAlpha = 0)
    {
        if ($fallbackBlock === null) $fallbackBlock = VanillaBlocks::BEDROCK();

        $this->fallbackBlock = $fallbackBlock;
        $this->heightColor = $heightColor;
        $this->heightAlpha = $heightAlpha;
    }

    /**
     * Get the fallback block
     * @return Block|null
     */
    public function getFallbackBlock(): ?Block
    {
        return $this->fallbackBlock;
    }

    /**
     * @return int
     */
    public function getHeightOverlayColor(): int
    {
        return $this->heightColor;
    }

    /**
     * @return int
     */
    public function getHeightOverlayAlpha(): int
    {
        return $this->heightAlpha;
    }
}