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
 * Copyright (c) 2024 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\utils;

use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\Utils;
use ZipArchive;

class ResourcePackUtils
{
    public const MANIFEST = "manifest.json";
    public const BLOCKS = "blocks.json";
    public const BIOMES_CLIENT = "biomes_client.json";
    public const TERRAIN_TEXTURE = "textures/terrain_texture.json";
    public const BLOCK_TEXTURES = "textures/blocks/";
    public const COLORMAP_TEXTURES = "textures/colormap/";

    public static function extractResourcePack(string $dest, ZippedResourcePack $pack, string $key = null, string $name = null): bool
    {
        // TODO: encrypted packs
        if ($key !== null) return false;

        // set the name to the uuid if not given
        if ($name === null) $name = $pack->getPackId();

        // open the zip archive
        $archive = new ZipArchive();
        if ($archive->open($pack->getPath()) !== true) return false;

        $rpPath = $dest . $name . "/";
        if (!is_dir($rpPath)) mkdir($rpPath . ResourcePackUtils::BLOCK_TEXTURES, 0777, true);

        $prefix = ResourcePackUtils::getPrefix($archive);
        if ($prefix === null) return false;

        // extract all json files
        $toExtract = [
            $prefix . self::MANIFEST,
            $prefix . self::BLOCKS,
            $prefix . self::BIOMES_CLIENT,
            $prefix . self::TERRAIN_TEXTURE,
        ];

        // extract the files from directories
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $name = Utils::assumeNotFalse($archive->getNameIndex($i), "This index should be valid");

            if (!str_starts_with($name, $prefix)) continue;
            $name = substr($name, strlen($prefix));

            if (str_starts_with($name, self::BLOCK_TEXTURES) || str_starts_with($name, self::COLORMAP_TEXTURES)) {
                $toExtract[] = $prefix . $name;
            }
        }

        // extract all files
        $archive->extractTo($rpPath, $toExtract);

        $archive->close();
        return true;
    }

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
     * Extracts the given resource pack from the resource_packs folder
     * @param string $dest the destination path
     * @param ZippedResourcePack $pack the pack to extract
     * @param string|null $key the encryption key
     * @return bool if the extraction was successful
     */
    public static function oldExtractResourcePack(string $dest, ZippedResourcePack $pack, string $key = null, string $name = null): bool
    {
        // TODO: encrypted packs
        if ($key !== null) return false;

        // set the name to the uuid if not given
        if ($name === null) $name = $pack->getPackId();

        // open the zip archive
        $archive = new ZipArchive();
        if ($archive->open($pack->getPath()) !== true) return false;

        $rpPath = $dest . $name . "/";
        if (!is_dir($rpPath)) mkdir($rpPath . ResourcePackUtils::BLOCK_TEXTURES, 0777, true);

        $prefix = ResourcePackUtils::getPrefix($archive);
        if ($prefix === null) return false;

        $manifest = $archive->getFromName($prefix . ResourcePackUtils::MANIFEST);
        if ($manifest === false) return false;

        file_put_contents($rpPath . ResourcePackUtils::MANIFEST, $manifest);

        $blocks = $archive->getFromName($prefix . ResourcePackUtils::BLOCKS);
        if ($blocks !== false) file_put_contents($rpPath . ResourcePackUtils::BLOCKS, $blocks);

        $terrainTexture = $archive->getFromName($prefix . ResourcePackUtils::TERRAIN_TEXTURE);
        if ($terrainTexture !== false) file_put_contents($rpPath . ResourcePackUtils::TERRAIN_TEXTURE, $terrainTexture);

        $blockTextures = ResourcePackUtils::getAllBlockTextures($archive, $prefix);

        // store all textures
        foreach ($blockTextures as $path) {
            $texture = $archive->getFromName($prefix . $path);
            if ($texture !== false) {
                $blockPath = str_replace($rpPath . ResourcePackUtils::BLOCK_TEXTURES, "", $rpPath . $path);
                if (sizeof($parts = explode("/", $blockPath)) > 1) {
                    array_pop($parts);
                    $blockFolderPath = $rpPath . ResourcePackUtils::BLOCK_TEXTURES . implode("/", $parts);
                    if (!is_dir($blockFolderPath)) mkdir($blockFolderPath, 0777, true);
                }

                file_put_contents($rpPath . $path, $texture);
            }
        }

        // close the archive
        $archive->close();

        return true;
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