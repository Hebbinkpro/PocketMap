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

namespace Hebbinkpro\PocketMap\textures;

use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\utils\block\BlockDataValues;
use Hebbinkpro\PocketMap\utils\block\BlockUtils;
use Hebbinkpro\PocketMap\utils\ResourcePackUtils;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePackManager;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\Filesystem;

class TerrainTextures extends ResourcePackTextures
{
    public const TERRAIN_TEXTURES = "terrain_textures.json";

    private string $path;
    private TerrainTexturesOptions $options;

    private ResourcePacksInfo $packs;

    private function __construct(string $path, TerrainTexturesOptions $options)
    {
        parent::__construct();
        $this->path = $path;
        $this->options = $options;
        $this->packs = new ResourcePacksInfo();
    }

    /**
     * Generate a (new) TerrainTextures instance
     * @param PluginBase $plugin
     * @param string $path
     * @param TerrainTexturesOptions $options
     * @return TerrainTextures|null
     */
    public static function generate(PluginBase $plugin, string $path, TerrainTexturesOptions $options): ?TerrainTextures
    {
        $lastTerrainTextures = self::fromExistingTextures($path, $options);
        $textures = new TerrainTextures($path, $options);

        $packs = $textures->getAllResourcePacks($plugin->getServer()->getResourcePackManager(), $lastTerrainTextures);
        if ($packs === null) return null;

        // pack list does not exist
        if ($lastTerrainTextures === null || $lastTerrainTextures->getPacks()->equals($packs)) {
            $textures->indexBlocks($packs);
        }

        $textures->loadFromFile();
        return $textures;
    }

    /**
     * Get a TerrainTextures instance from the given path
     * @param string $path
     * @param TerrainTexturesOptions $options
     * @return TerrainTextures|null
     */
    public static function fromExistingTextures(string $path, TerrainTexturesOptions $options): ?TerrainTextures
    {
        if (!is_file($path . self::TERRAIN_TEXTURES)) return null;

        $textures = new TerrainTextures($path, $options);
        if (!$textures->loadFromFile()) return null;

        return $textures;
    }

    /**
     * Loads all data from the terrain_textures file in memory
     * @return bool true if success, false otherwise
     */
    private function loadFromFile(): bool
    {
        if (!is_file($this->path . self::TERRAIN_TEXTURES)) return false;

        $fileContents = file_get_contents($this->path . self::TERRAIN_TEXTURES);
        if ($fileContents === false) return false;

        $contents = json_decode($fileContents, true) ?? [];
        $requiredKeys = ["packs", "textures", "terrain_textures", "blocks"];
        if (!is_array($contents) ||
            sizeof(array_intersect(array_keys($contents), $requiredKeys)) < sizeof($requiredKeys)) {
            return false;
        }

        $this->packs = ResourcePacksInfo::fromArray($contents["packs"]);
        $this->textures = $contents["textures"];
        $this->terrainTextures = $contents["terrain_textures"];
        $this->blocks = $contents["blocks"];

        return true;
    }

    /**
     * Extract all resource packs inside the server's resource_packs folder
     * @param ResourcePackManager $manager
     * @param TerrainTextures|null $lastTerrainTextures
     * @return null|ResourcePacksInfo
     */
    private function getAllResourcePacks(ResourcePackManager $manager, ?TerrainTextures $lastTerrainTextures = null): ?ResourcePacksInfo
    {
        if (!is_dir($this->path)) mkdir($this->path);

        $lastLoaded = [];
        if ($lastTerrainTextures !== null) $lastLoaded = $lastTerrainTextures->getPacks()->getResourcePacks() ?? [];

        $loaded = [];

        $packs = $manager->getResourceStack();
        foreach ($packs as $pack) {
            // get the zipped resource pack
            $uuid = $pack->getPackId();
            if (!$pack instanceof ZippedResourcePack) continue;

            $filePath = explode("/", $pack->getPath());
            $file = $filePath[array_key_last($filePath)];
            $hash = mb_convert_encoding($pack->getSha256(), "UTF-8", "ISO-8859-1");

            $info = new ResourcePackInfo($uuid, $file, $pack->getPackVersion(), $hash);


            // this pack is already loaded in a previous startup
            if (in_array($info, $lastLoaded, true)) {
                $loaded[$uuid] = $info;
                continue;
            }

            $key = $manager->getPackEncryptionKey($uuid);
            if (ResourcePackUtils::extractResourcePack($this->path, $pack, $key)) {
                $loaded[$uuid] = $info;
            }
        }

        $files = scandir($this->path);
        if ($files === false) return null;

        foreach ($files as $file) {
            $path = $this->path . $file;
            // not a dir or the current vanilla resource pack
            if (in_array($file, [".", "..", PocketMap::RESOURCE_PACK_NAME], true) || !is_dir($path)) continue;

            // remove unused dirs
            if (!array_key_exists($file, $loaded)) Filesystem::recursiveUnlink($path);
        }

        return new ResourcePacksInfo(PocketMap::RESOURCE_PACK_NAME, $loaded);
    }

    /**
     * @return ResourcePacksInfo
     */
    public function getPacks(): ResourcePacksInfo
    {
        return $this->packs;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }


    /**
     * @param ResourcePacksInfo $packs
     * @return void
     */
    private function indexBlocks(ResourcePacksInfo $packs): void
    {
        // load the vanilla textures
        $textures = ResourcePackTextures::getFromPath($this->path . PocketMap::RESOURCE_PACK_NAME . "/");

        foreach ($packs->getResourcePacks() as $packInfo) {
            $uuid = $packInfo->getUuid();

            // get the textures inside the pack
            $packTextures = ResourcePackTextures::getFromPath($this->path . $uuid . "/", $uuid);

            // merge the textures inside the pack with the existing textures
            $textures->merge($packTextures);
        }

        // generate the block index structure
        $terrainTextures = [
            "packs" => $packs->jsonSerialize(),
            "textures" => $textures->getTextures(),
            "terrain_textures" => $textures->getTerrainTextures(),
            "blocks" => $textures->getBlocks()
        ];

        // store the block index
        file_put_contents($this->path . self::TERRAIN_TEXTURES, json_encode($terrainTextures));
    }

    public function getVanillaPath(): ?string
    {
        return $this->path . PocketMap::RESOURCE_PACK_NAME . "/";
    }

    /**
     * Get a blocks texture path
     * @param Block $block
     * @return string|null
     */
    public function getBlockTexturePath(Block $block): ?string
    {
        $texture = $this->getTextureByBlock($block);
        // no texture, or block is invisible
        if ($texture === null || strlen($texture) == 0) return null;

        return $this->getRealTexturePath($texture);
    }

    /**
     * Get the texture of a block
     * @param Block $block
     * @return string|null
     */
    public function getTextureByBlock(Block $block): string|null
    {
        // it's a hidden block
        if (BlockUtils::isInvisible($block)) return "";

        $textureName = TextureUtils::getBlockTextureName($block);
        if ($textureName === null) return null;

        $blockTexture = $this->getBlockByName($textureName);
        if ($blockTexture !== null) {
            if (is_array($blockTexture)) {
                // it has directions
                $face = TextureUtils::getBlockFaceTexture($block, array_keys($blockTexture));
                if ($face === null) return null;

                $blockTexture = $blockTexture[$face];
            }

            /** @var string $textureName */
            $textureName = $blockTexture;
        }

        $terrainTexture = $this->getTerrainTextureByName($textureName);
        if ($terrainTexture !== null) {
            if (is_array($terrainTexture)) {
                // we need a block data value
                $dataValue = BlockDataValues::getDataValue($block);
                $terrainTexture = $terrainTexture[$dataValue] ?? null;
                if ($terrainTexture === null) return null;
            } else {
                // split the texture name and get the last index
                $split = explode("_", $textureName);
                $key = sizeof($split) - 1;
                if (is_numeric($split[$key])) {
                    // last value is an integer, so it is probably data driven
                    // replace the last integer with the correct data value
                    // this behavior is needed for sweet berry bushes...
                    $dataValue = BlockDataValues::getDataValue($block);
                    $split[$key] = $dataValue;
                    $dataTextureName = implode("_", $split);

                    $newTerrainTexture = $this->getTerrainTextureByName($dataTextureName);
                    // only use the updated texture if it is a string
                    if (is_string($newTerrainTexture)) $terrainTexture = $newTerrainTexture;

                }
            }

            /** @var string $textureName */
            $textureName = $terrainTexture;
        }

        return $this->getTextureByName($textureName);
    }

    /**
     * Get the real path to a texture file
     * @param string $path
     * @return string
     */
    public function getRealTexturePath(string $path): string
    {
        // vanilla textures don't have a prefix, so we have to add it
        if (str_starts_with($path, "textures/")) return $this->path . PocketMap::RESOURCE_PACK_NAME . "/" . $path;
        // texture path is already valid
        return $this->path . $path;
    }

    /**
     * @return TerrainTexturesOptions
     */
    public function getOptions(): TerrainTexturesOptions
    {
        return $this->options;
    }
}