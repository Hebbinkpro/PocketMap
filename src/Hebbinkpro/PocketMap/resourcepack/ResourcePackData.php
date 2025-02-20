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

use Ahc\Json\Comment as CommentedJsonDecoder;
use Hebbinkpro\PocketMap\resourcepack\biomes\BiomesClientData;
use Hebbinkpro\PocketMap\resourcepack\blocks\BlocksData;
use Hebbinkpro\PocketMap\resourcepack\terraintextures\TerrainTextureData;
use JsonMapper;
use JsonMapper_Exception;
use pocketmine\resourcepacks\json\Manifest;
use pocketmine\resourcepacks\ResourcePackException;
use pocketmine\utils\Utils;
use Ramsey\Uuid\Uuid;
use ZipArchive;

/**
 * Class used to extract all essential data from a resource pack (folder, .zip or .mcpack)
 */
final class ResourcePackData
{
    public const MANIFEST_FILE = "manifest.json";
    public const BLOCKS_FILE = "blocks.json";
    public const BIOMES_CLIENT_FILE = "biomes_client.json";
    public const TERRAIN_TEXTURE_FILE = "textures/terrain_texture.json";
    public const BLOCKS_DIR = "textures/blocks/";
    public const COLOR_MAP_DIR = "textures/colormap/";
    public const ENTITY_DIR = "textures/entity/";
    public const ENTITY_TEXTURES = [
        "banner",
        "bed",
        "bell",
        "chest",
        "piston_arm",
        "skulls/",
        "_sign",
        "sign_"
    ];

    private string $uuid;
    private ?BlocksData $blocks;
    private ?TerrainTextureData $terrainTexture;
    private ?BiomesClientData $biomesClient;
    private ResourcePackTextures $textures;

    /**
     * @param string $uuid
     * @param BlocksData|null $blocks
     * @param TerrainTextureData|null $terrainTexture
     * @param BiomesClientData|null $biomesClient
     * @param ResourcePackTextures $textures
     */
    public function __construct(string $uuid, ?BlocksData $blocks, ?TerrainTextureData $terrainTexture, ?BiomesClientData $biomesClient, ResourcePackTextures $textures)
    {
        $this->uuid = $uuid;
        $this->blocks = $blocks;
        $this->terrainTexture = $terrainTexture;
        $this->biomesClient = $biomesClient;
        $this->textures = $textures;
    }

    /**
     * Load data from a resource pack at the given path
     * @param string $path the path of the resource pack to extract
     * @returns ResourcePackData the loaded resource pack data
     * @throws JsonMapper_Exception|ResourcePackException
     */
    public static function load(string $path): ?ResourcePackData
    {
        $isFile = is_file($path);

        // extract the path which contains the resource pack data from the given file/folder
        if ($isFile) $dataPath = self::getDataPathFromZip($path);
        else $dataPath = self::getDataPathFromFolder($path);

        // invalid data path
        if ($dataPath === null) return null;

        // list of files we want to extract as keys, with extracted data as values
        $files = [
            self::MANIFEST_FILE,
            self::BLOCKS_FILE,
            self::BIOMES_CLIENT_FILE,
            self::TERRAIN_TEXTURE_FILE,
        ];

        $manifest = null;
        $blocks = null;
        $terrainTexture = null;
        $biomesClient = null;

        // get the data of all requested files
        foreach ($files as $file) {

            // retrieve the file contents
            try {
                $contents = file_get_contents($dataPath . $file);
                if ($contents === false) continue;
            } catch (\ErrorException) {
                // thrown when file cannot be found
                continue;
            }

            // get manifest data
            if ($file === self::MANIFEST_FILE) {
                $data = (new CommentedJsonDecoder())->decode($contents);
                $manifest = (new JsonMapper())->map($data, new Manifest());
                continue;
            }

            // get other file data
            $data = (new CommentedJsonDecoder())->decode($contents, true);
            switch ($file) {
                case self::BLOCKS_FILE:
                    $blocks = BlocksData::parse($data);
                    break;
                case self::TERRAIN_TEXTURE_FILE:
                    $terrainTexture = TerrainTextureData::parse($data);
                    break;
                case self::BIOMES_CLIENT_FILE:
                    $biomesClient = BiomesClientData::parse($data);
                    break;
            }

        }

        // manifest is required
        if ($manifest === null) throw new ResourcePackException("No Manifest file not found at path '$dataPath'");

        // get the textures
        if ($isFile) $textures = self::getTexturesFromZip($dataPath);
        else $textures = self::getTexturesFromFolder($dataPath);

        // return the resource pack data
        return new ResourcePackData(
            $manifest->header->uuid,
            $blocks,
            $terrainTexture,
            $biomesClient,
            $textures
        );
    }

    /**
     * Return a path to the folder inside the zip that contains the manifest.json
     *
     * The code of validating the file and finding the manifest
     *  is taken from `pocketmine\resourcepacks\ZippedResourcePack`.
     * @param string $path
     * @return string|null
     */
    private static function getDataPathFromZip(string $path): ?string
    {


        $size = filesize($path);
        if ($size === false) {
            throw new ResourcePackException("Unable to determine size of file");
        }
        if ($size === 0) {
            throw new ResourcePackException("Empty file, probably corrupted");
        }

        $archive = new ZipArchive();
        if (($openResult = $archive->open($path)) !== true) {
            throw new ResourcePackException("Encountered ZipArchive error code $openResult while trying to open $path");
        }

        $zipContentsPath = "zip://$path#";

        // check if the manifest is not in a subfolder
        if ($archive->locateName(self::MANIFEST_FILE) !== false) {
            $archive->close();
            return $zipContentsPath;
        }

        // search for the best manifest.json match
        $manifestPath = null;
        $manifestIdx = null;
        for ($i = 0; $i < $archive->numFiles; ++$i) {
            $name = Utils::assumeNotFalse($archive->getNameIndex($i), "This index should be valid");
            if (
                ($manifestPath === null || strlen($name) < strlen($manifestPath)) &&
                preg_match('#.*/manifest.json$#', $name) === 1
            ) {
                $manifestPath = $name;
                $manifestIdx = $i;
            }
        }

        if ($manifestIdx !== null) {

            $archive->close();
            $prefix = str_replace(self::MANIFEST_FILE, "", $manifestPath);
            return $zipContentsPath . $prefix;

        } elseif ($archive->locateName("pack_manifest.json") !== false) {
            throw new ResourcePackException("Unsupported old pack format");
        } else {
            throw new ResourcePackException("manifest.json not found in the archive root");
        }
    }

    /**
     * Return a path to the folder inside the given folder that contains the manifest.json
     *
     * The search algorithm makes use of breadth first search
     *
     * @param string $path
     * @return string|null
     */
    private static function getDataPathFromFolder(string $path): ?string
    {

        if (!is_dir($path)) return null;

        // make sure the path ends on a dir separator
        if (!str_ends_with($path, "/") && !str_ends_with($path, "\\")) $path .= "/";

        // check if the manifest is in the given folder
        if (is_file($path . self::MANIFEST_FILE)) {
            return $path;
        }

        // set default values
        $paths = [$path];
        $subPaths = [];

        // loop until we find the manifest
        while (sizeof($paths) > 0) {
            // loop over all current paths
            foreach ($paths as $path) {
                // check if the manifest is inside
                if (is_file($path . self::MANIFEST_FILE)) {
                    return $path;
                }

                // get all files in the dir and append all sub dirs to sub paths
                foreach (scandir($path) as $file) {
                    $folderPath = $path . $file . "/";
                    if ($file === "." || $file === ".." || !is_dir($folderPath)) continue;

                    $subPaths[] = $folderPath;
                }
            }

            // replace paths by the sub paths
            $paths = [...$subPaths];
            // make sub paths empty
            $subPaths = [];
        }

        // nothing has been found
        return null;
    }

    /**
     * Get all resource pack textures from a zip file
     * @param string $path zip file path zip://zip_path#folder_path
     * @return ResourcePackTextures
     */
    private static function getTexturesFromZip(string $path): ResourcePackTextures
    {

        $parts = explode("#", $path);
        $zipPath = $parts[0];
        if (sizeof($parts) > 1) $prefix = $parts[1];
        else $prefix = "";


        $zipFilePath = str_replace("zip://", "", $zipPath);
        $archive = new ZipArchive();
        if (($openResult = $archive->open($zipFilePath)) !== true) {
            throw new ResourcePackException("Encountered ZipArchive error code $openResult while trying to open $path");
        }

        $textures = ResourcePackTextures::empty();

        for ($i = 0; $i < $archive->numFiles; ++$i) {
            $name = Utils::assumeNotFalse($archive->getNameIndex($i), "This index should be valid");

            // get the file path
            $filePath = $zipPath . "#" . $name;
            if (str_starts_with($name, $prefix . self::BLOCKS_DIR)) {
                $texture = TextureFile::fromPath($filePath);
                if ($texture !== null) $textures->addBlock($texture);
            } elseif (str_starts_with($name, $prefix . self::COLOR_MAP_DIR)) {
                $texture = TextureFile::fromPath($filePath);
                if ($texture !== null) $textures->addColorMap($texture);
            } elseif (str_starts_with($name, $prefix . self::ENTITY_DIR)) {
                foreach (self::ENTITY_TEXTURES as $t) {
                    if (sizeof(explode($t, $name)) > 1) {
                        // found a matching entity
                        $texture = TextureFile::fromPath($filePath);
                        if ($texture !== null) $textures->addEntity($texture);
                        break;
                    }
                }
            }


        }


        $archive->close();
        return $textures;
    }

    /**
     * Get all resource pack textures from a folder
     * @param string $path path to the folder containing the manifest.json
     * @return ResourcePackTextures
     */
    private static function getTexturesFromFolder(string $path): ResourcePackTextures
    {

        $blocks = TextureFile::getTexturesFromDir($path . self::BLOCKS_DIR);
        $colorMaps = TextureFile::getTexturesFromDir($path . self::COLOR_MAP_DIR);
        $entities = []; // TODO extract selected block entities


        if (is_dir($path . self::ENTITY_DIR) &&
            ($files = scandir($path . self::ENTITY_DIR)) !== false) {

            /** @var string[] $textures */
            $textures = [...self::ENTITY_TEXTURES];

            // singular files which must contain the 'texture' in their name
            foreach ($files as $file) for ($i = 0; $i < sizeof($textures); $i++) {
                $t = $textures[$i];

                // split to check if the file contains the texture
                if (sizeof(explode($t, $file)) <= 1) continue;

                // check if the file is a directory
                if (is_dir($path . self::ENTITY_DIR . $file)) {
                    $entities = array_merge($entities, TextureFile::getTexturesFromDir($path . self::ENTITY_DIR . $file . "/"));
                } else {
                    // the file contains the texture
                    $texture = TextureFile::fromPath($path . self::ENTITY_DIR . $file);
                    $entities[$texture->getName()] = $texture;
                }

                // remove the matched texture, it should not occur again
                unset($textures[$i]);
                break;

            }


        }




        return new ResourcePackTextures($blocks, $colorMaps, $entities);

    }

    /**
     * Merge the given resource pack with this resource pack.
     *
     * - If both resource packs contain the same data, the given pack has priority.
     * - The manifest file of this resource pack will be used.
     * @param ResourcePackData $rp
     * @return ResourcePackData a new resource pack data instance
     */
    public function merge(ResourcePackData $rp): ResourcePackData
    {

        // create a new, deterministic, UUID by combining the uuid's
        $uuid = Uuid::uuid3(Uuid::NIL, $this->uuid . $rp->getUUID());

        if ($this->blocks !== null && $rp->getBlocks() !== null) {
            // merge the blocks data
            $blocks = $this->blocks->merge($rp->getBlocks());
        } else {
            // get one of the blocks data
            $blocks = $this->blocks ?? $rp->getBlocks() ?? null;
        }

        if ($this->terrainTexture !== null && $rp->getTerrainTexture() !== null) {
            // merge the blocks data
            $terrainTexture = $this->terrainTexture->merge($rp->getTerrainTexture());
        } else {
            // get one of the blocks data
            $terrainTexture = $this->terrainTexture ?? $rp->getTerrainTexture() ?? null;
        }

        if ($this->biomesClient !== null && $rp->getBiomesClient() !== null) {
            // merge the blocks data
            $biomesClient = $this->biomesClient->merge($rp->getBiomesClient());
        } else {
            // get one of the blocks data
            $biomesClient = $this->biomesClient ?? $rp->getBiomesClient() ?? null;
        }

        $textures = $this->textures->merge($rp->getTextures());

        return new ResourcePackData(
            $uuid,
            $blocks,
            $terrainTexture,
            $biomesClient,
            $textures
        );
    }

    /**
     * @return string
     */
    public function getUUID(): string
    {
        return $this->uuid;
    }

    /**
     * @return BlocksData|null
     */
    public function getBlocks(): ?BlocksData
    {
        return $this->blocks;
    }

    /**
     * @return TerrainTextureData|null
     */
    public function getTerrainTexture(): ?TerrainTextureData
    {
        return $this->terrainTexture;
    }

    /**
     * @return BiomesClientData|null
     */
    public function getBiomesClient(): ?BiomesClientData
    {
        return $this->biomesClient;
    }

    /**
     * @return ResourcePackTextures
     */
    public function getTextures(): ResourcePackTextures
    {
        return $this->textures;
    }
}