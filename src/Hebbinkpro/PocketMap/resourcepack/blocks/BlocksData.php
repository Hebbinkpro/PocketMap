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

namespace Hebbinkpro\PocketMap\resourcepack\blocks;

use Hebbinkpro\PocketMap\resourcepack\ResourcePackParser;
use pocketmine\resourcepacks\ResourcePackException;

final class BlocksData implements ResourcePackParser
{
    public const FORMAT_VERSION = "format_version";

    private string $formatVersion;

    /** @var array<string, BlocksTextures> */
    private array $blocks;

    public function __construct(string $formatVersion, array $blocks)
    {
        $this->formatVersion = $formatVersion;
        $this->blocks = $blocks;
    }

    /**
     * Decode data from the blocks.json file into something more useful
     * @param array $data
     * @return BlocksData
     */
    public static function parse(array $data): BlocksData
    {
        $formatVersion = null;

        $blocks = [];
        foreach ($data as $key => $value) {
            if ($key === self::FORMAT_VERSION) {
                $formatVersion = strval($value);
                continue;
            }

            // add the block textures
            if (($textures = BlocksTextures::parse($key, $value)) !== null) {
                $blocks[$key] = $textures;
            }

        }

        if ($formatVersion === null) throw new ResourcePackException("No format_version found in blocks.json");

        return new BlocksData($formatVersion, $blocks);
    }

    /**
     * @return string
     */
    public function getFormatVersion(): string
    {
        return $this->formatVersion;
    }

    /**
     * Get block textures by a block name
     * @param string $blockName
     * @return BlocksTextures|null
     */
    public function getBlock(string $blockName): ?BlocksTextures
    {
        return $this->blocks[$blockName] ?? null;
    }

    /**
     * Merge blocks data.
     *
     * The given blocks data can overwrite data of the current blocks data.
     * @param BlocksData $data
     * @return BlocksData a new blocks data instance with the merged values
     */
    public function merge(BlocksData $data): BlocksData
    {
        return new BlocksData(
            $this->formatVersion,
            array_merge($this->blocks, $data->getBlocks())
        );
    }

    /**
     * @return array<string, BlocksTextures>
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }
}