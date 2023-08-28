<?php

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

        if ($manifestIdx === null || $archive->getFromIndex($manifestIdx) === false) {
            return null;
        }

        return str_replace(self::MANIFEST, "", $manifestPath);
    }

    /**
     * Get all block textures inside the archive
     * @param ZipArchive $archive
     * @param string $prefix
     * @return array
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