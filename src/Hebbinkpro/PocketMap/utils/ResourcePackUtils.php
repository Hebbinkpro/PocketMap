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

namespace Hebbinkpro\PocketMap\utils;

use pocketmine\utils\Utils;
use ZipArchive;

class ResourcePackUtils
{
    public const MANIFEST = "manifest.json";
    public const BLOCKS = "blocks.json";
    public const TERRAIN_TEXTURE = "textures/terrain_texture.json";
    public const BLOCK_TEXTURES = "textures/blocks/";

    /**
     * Get the prefix to the resource pack data inside the archive
     * @param ZipArchive $archive
     * @return string|null
     */
    public static function getPrefix(ZipArchive $archive): ?string
    {

        if ($archive->getFromName(self::MANIFEST) !== false) return "";

        $manifestPath = null;
        $manifestIdx = null;
        for ($i = 0; $i < $archive->numFiles; ++$i) {
            $name = Utils::assumeNotFalse($archive->getNameIndex($i), "This index should be valid");
            if (
                ($manifestPath === null || strlen($name) < strlen($manifestPath)) &&
                preg_match('#.*/' . self::MANIFEST . '$#', $name) === 1
            ) {
                $manifestPath = $name;
                $manifestIdx = $i;
            }
        }

        if ($manifestPath === null || $manifestIdx === null || $archive->getFromIndex($manifestIdx) === false) {
            return null;
        }

        return str_replace(self::MANIFEST, "", $manifestPath);
    }

    /**
     * Get all block textures inside the archive
     * @param ZipArchive $archive
     * @param string $prefix
     * @return array<mixed>
     */
    public static function getAllBlockTextures(ZipArchive $archive, string $prefix = ""): array
    {
        $path = $prefix . self::BLOCK_TEXTURES;

        $blocks = [];

        for ($i = 0; $i < $archive->numFiles; ++$i) {
            $texture = Utils::assumeNotFalse($archive->getNameIndex($i), "This index should be valid");
            if (str_starts_with($texture, $path)) {
                if (!str_ends_with($texture, ".png") && !str_ends_with($texture, ".tga")) continue;

                $name = str_replace([$path, ".png", ".tga"], "", $texture);
                $blocks[$name] = $texture;
            }
        }

        return $blocks;
    }
}