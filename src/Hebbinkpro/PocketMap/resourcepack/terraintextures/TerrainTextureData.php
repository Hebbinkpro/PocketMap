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

namespace Hebbinkpro\PocketMap\resourcepack\terraintextures;

use Hebbinkpro\PocketMap\resourcepack\blocks\BlocksTextures;
use Hebbinkpro\PocketMap\resourcepack\ResourcePackParser;
use pocketmine\resourcepacks\ResourcePackException;

final class TerrainTextureData implements ResourcePackParser
{
    public const RESOURCE_PACK_NAME = "resource_pack_name";
    public const TEXTURE_NAME = "texture_name";
    public const PADDING = "padding";
    public const NUM_MIP_LEVELS = "num_mip_levels";
    public const TEXTURE_DATA = "texture_data";

    private string $resourcePackName;
    private string $textureName;
    private int $padding;
    private int $numMipLevels;

    /** @var array<string, BlocksTextures> */
    private array $textureData;

    /**
     * @param string $resourcePackName
     * @param string $textureName
     * @param int $padding
     * @param int $numMipLevels
     * @param array<string, BlocksTextures> $textureData
     */
    public function __construct(string $resourcePackName, string $textureName, int $padding, int $numMipLevels, array $textureData)
    {
        $this->resourcePackName = $resourcePackName;
        $this->textureName = $textureName;
        $this->padding = $padding;
        $this->numMipLevels = $numMipLevels;
        $this->textureData = $textureData;
    }

    public static function parse(array $data): TerrainTextureData
    {
        // only resource pack name is required
        $resourcePackName = null;
        $textureName = "atlas.terrain";
        $padding = 8;
        $numMipLevels = 4;
        $textureData = [];

        foreach ($data as $key => $value) {
            switch ($key) {
                case self::RESOURCE_PACK_NAME:
                    $resourcePackName = strval($value);
                    break;
                case self::TEXTURE_NAME:
                    $textureName = strval($value);
                    break;
                case self::PADDING:
                    $padding = intval($value);
                    break;
                case self::NUM_MIP_LEVELS:
                    $numMipLevels = intval($value);
                    break;
                case self::TEXTURE_DATA:
                    // parse all blocks
                    foreach ($value as $block => $texture) {
                        $textureData[$block] = TerrainTextureTextures::parse($block, $texture);
                    }
            }
        }

        if ($resourcePackName === null)
            throw new ResourcePackException(self::RESOURCE_PACK_NAME . " not found in terrain_texture.json");

        return new TerrainTextureData($resourcePackName, $textureName, $padding, $numMipLevels, $textureData);
    }

    /**
     * @return string
     */
    public function getResourcePackName(): string
    {
        return $this->resourcePackName;
    }

    /**
     * @return string
     */
    public function getTextureName(): string
    {
        return $this->textureName;
    }

    /**
     * @return int
     */
    public function getPadding(): int
    {
        return $this->padding;
    }

    /**
     * @return int
     */
    public function getNumMipLevels(): int
    {
        return $this->numMipLevels;
    }

    /**
     * Merge terrain texture data.
     *
     * The given terrain texture data can overwrite texture data of the current texture data.
     * @param TerrainTextureData $data
     * @return TerrainTextureData a new texture data instance with the merged values
     */
    public function merge(TerrainTextureData $data): TerrainTextureData
    {
        return new TerrainTextureData(
            $this->resourcePackName,
            $this->textureName,
            $this->padding,
            $this->numMipLevels,
            array_merge($this->textureData, $data->getTextureData())
        );
    }

    /**
     * @return array
     */
    public function getTextureData(): array
    {
        return $this->textureData;
    }
}