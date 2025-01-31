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

namespace Hebbinkpro\PocketMap\resourcepack;

use Hebbinkpro\PocketMap\resourcepack\blocks\BlocksTextures;
use pocketmine\resourcepacks\ResourcePackException;

abstract class TexturesData
{
    private string $blockName;

    /** @var string[] */
    private array $textures;

    public function __construct(string $blockName, array $textures)
    {
        self::validateTextures($textures);

        $this->blockName = $blockName;
        $this->textures = $textures;
    }

    final public static function validateTextures(array $textures): void
    {
        foreach ($textures as $texture) {
            if (!is_string($texture)) {
                var_dump($textures);
                throw new ResourcePackException("Non string texture path found");
            }
        }
    }

    /**
     * Parse the unique textures of a block from the terrain_texture.json data
     * @param string $blockName key of the data
     * @param array{textures: string|string[]} $data value
     * @return BlocksTextures|null
     */
    public abstract static function parse(string $blockName, array $data): ?TexturesData;

    /**
     * @return string
     */
    public function getBlockName(): string
    {
        return $this->blockName;
    }

    /**
     * @return array
     */
    public function getTextures(): array
    {
        return $this->textures;
    }
}