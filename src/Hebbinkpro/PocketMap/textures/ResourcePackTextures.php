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

use Hebbinkpro\PocketMap\utils\ResourcePackUtils;

class ResourcePackTextures
{
    /** @var array<string, string> List containing all textures inside /textures/blocks/ */
    protected array $textures;
    /** @var array<string, string|array<mixed>> List of all texture aliases mapping a texture defined in /textures/terrain_texture.json */
    protected array $terrainTextures;
    /** @var array<string, string|array<mixed>> List of all blocks mapping a texture alias defined in /blocks.json */
    protected array $blocks;


    protected function __construct()
    {
        $this->textures = [];
        $this->terrainTextures = [];
        $this->blocks = [];
    }

    public static function getFromPath(string $path, string $prefix = ""): ResourcePackTextures
    {
        $resourcePackTextures = new ResourcePackTextures();
        $resourcePackTextures->loadTextures($path . ResourcePackUtils::BLOCK_TEXTURES, $prefix);
        $resourcePackTextures->loadTerrainTextures($path . ResourcePackUtils::TERRAIN_TEXTURE);
        $resourcePackTextures->loadBlocks($path . ResourcePackUtils::BLOCKS);

        return $resourcePackTextures;
    }

    /**
     * Get a list of all textures inside the /textures/blocks path of the resource pack
     * @return void a list of all the available blocks. [<name> => /textures/blocks/<name>, ...]
     */
    private function loadTextures(string $path, string $prefix): void
    {
        if (strlen($prefix) > 0) $prefix .= "/";

        $this->textures = [];

        $contents = scandir($path);
        if ($contents === false) return;

        foreach ($contents as $block) {
            if (in_array($block, [".", ".."], true)) continue;

            $blockPrefix = "";
            $blocks = [$block];

            if (is_dir($path . $block)) {
                $blocks = scandir($path . $block);
                if ($blocks === false) continue;
                $blockPrefix .= $block . "/";
            } else if (!is_file($path . $block)) continue;

            foreach ($blocks as $file) {
                if (str_ends_with($file, ".png") || str_ends_with($file, ".tga")) {
                    $name = str_replace([".png", ".tga"], "", $file);
                    if (!array_key_exists($name, $this->textures)) {
                        $this->textures[$name] = $prefix . ResourcePackUtils::BLOCK_TEXTURES . $blockPrefix . $name;
                    }
                }
            }
        }

    }

    private function loadTerrainTextures(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $fileContents = file_get_contents($path);
        if ($fileContents === false) return;

        $contents = json_decode($fileContents, true) ?? [];
        if (!is_array($contents) || !isset($contents["texture_data"])) return;

        /** @var array{texture_data: array<string, array{textures?: string|array<mixed>|mixed}>} $contents */

        $this->terrainTextures = [];

        foreach ($contents["texture_data"] as $name => $data) {
            if (!isset($data["textures"])) continue;
            $texture = $data["textures"];

            // name:texture
            if (is_string($texture)) {
                $textureName = self::getTextureName($texture);
                if (self::isValidTexture($textureName)) {
                    $this->terrainTextures[$name] = $textureName;
                }
                continue;
            }

            if (is_array($texture)) {
                // name: {path: texture}
                if (isset($texture["path"])) {
                    $textureName = self::getTextureName($texture["path"]);
                    if (self::isValidTexture($textureName)) {
                        $this->terrainTextures[$name] = $textureName;
                    }
                    continue;
                }

                $textureList = [];
                foreach ($texture as $key => $item) {
                    $textureName = null;
                    // name: [texture, ...]
                    if (is_string($item)) $textureName = self::getTextureName($item);
                    // name: [{path: texture}, ...]
                    if (is_array($item) && isset($item["path"])) $textureName = self::getTextureName($item["path"]);

                    if ($textureName !== null && self::isValidTexture($textureName)) {
                        $textureList[$key] = $textureName;
                    }
                }

                // set the terrain textures
                if (sizeof($textureList) > 0) $this->terrainTextures[$name] = [];

            }
        }

    }

    public function getTextureName(string $path): string
    {
        $parts = explode("/", $path);
        return $parts[array_key_last($parts)];
    }

    public function isValidTexture(string $name): bool
    {
        return array_key_exists($name, $this->textures);
    }

    private function loadBlocks(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $fileContents = file_get_contents($path);
        if ($fileContents === false) return;
        $contents = json_decode($fileContents, true) ?? [];
        if (!is_array($contents)) return;

        /** @var array<string, array{textures: mixed}> $contents */

        $this->blocks = [];
        foreach ($contents as $name => $data) {
            if (!isset($data["textures"])) continue;
            $texture = $data["textures"];

            if (is_string($texture)) {
                // check if it is a valid terrain texture
                if (self::isValidTerrainTexture($texture)) {
                    $this->blocks[$name] = $texture;
                } // check if the texture has stages but is not included in the terrain_texture.json
                elseif (($stages = $this->getTextureStages($texture)) !== null && sizeof($stages) > 0) {
                    // add the stages to the terrain textures
                    $this->terrainTextures[$texture] = $stages;
                    $this->blocks[$name] = $texture;
                } // check if the texture is just a valid block texture
                elseif (self::isValidTexture($texture)) {
                    $this->blocks[$name] = $texture;
                }
            }

            if (is_array($texture)) {
                $blocks = [];
                foreach ($texture as $direction => $directionAlias) {
                    /** @var string $directionAlias */

                    // check if it is a valid terrain texture
                    if (self::isValidTerrainTexture($directionAlias)) {
                        $blocks[$direction] = $directionAlias;
                    } // check if the texture has stages but is not included in the terrain_texture.json
                    elseif (($stages = $this->getTextureStages($directionAlias)) !== null && sizeof($stages) > 0) {
                        // add the stages to the terrain textures
                        $this->terrainTextures[$directionAlias] = $stages;
                        $blocks[$direction] = $directionAlias;
                    } // check if the texture is just a valid block texture
                    elseif (self::isValidTexture($directionAlias)) {
                        $blocks[$direction] = $directionAlias;
                    }
                }

                // remove empty list
                if (sizeof($blocks) > 0) $this->blocks[$name] = $blocks;
            }
        }


    }

    public function isValidTerrainTexture(string $alias): bool
    {
        return array_key_exists($alias, $this->terrainTextures);
    }

    /**
     * @param string $texture
     * @return array<int, string>|null
     */
    public function getTextureStages(string $texture): ?array
    {
        $stages = [];

        $stage = 0;
        while (true) {
            $stageTexture = $texture . "_stage_" . $stage;
            // check if the stage texture exists
            if (array_key_exists($stageTexture, $this->textures)) $stages[$stage] = $stageTexture;
            // We need to have at least checked 8 stages (e.g. pitcher_crop_top starts at stage 3)
            else if (sizeof($stages) > 0 || $stage >= 8) break;

            // increase the stage
            $stage++;
        }

        // no stages found
        if (sizeof($stages) == 0) return null;

        if (sizeof($stages) < $stage && array_key_exists($texture, $this->textures)) {
            $placeholder = $texture;
            $toFill = array_diff(array_keys(array_fill(0, $stage - 1, 0)), array_keys($stages));
            foreach ($toFill as $i) {
                $stages[$i] = $placeholder;
            }
        }

        sort($stages);

        return $stages;
    }

    /**
     * Merge the given pack with this pack.
     * The given pack can only overwrite not yet overwritten textures (textures starting with /textures/block/...)
     * @param ResourcePackTextures $pack
     * @return void
     */
    public function merge(ResourcePackTextures $pack): void
    {

        $this->mergeTextures($pack->getTextures());
        $this->mergeTextureAliases($pack->getTerrainTextures());
        $this->mergeBlockTextureAliases($pack->getBlocks());

    }

    /**
     * @param array<string, string> $childTextures
     * @return void
     */
    private function mergeTextures(array $childTextures): void
    {

        foreach ($childTextures as $name => $childTexture) {
            $texture = $this->getTexture($name);

            // we cannot overwrite the texture
            if ($texture !== null && !str_starts_with($texture, ResourcePackUtils::BLOCK_TEXTURES)) {
                continue;
            }

            // overwrite the texture
            $this->textures[$name] = $childTexture;
        }

    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getTexture(string $name): null|string
    {
        return $this->textures[$name] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getTextures(): array
    {
        return $this->textures;
    }

    /**
     * @param array<string, string|array<mixed>> $childTextureAliases
     * @return void
     */
    private function mergeTextureAliases(array $childTextureAliases): void
    {

        foreach ($childTextureAliases as $alias => $childTextureName) {
            if (isset($this->terrainTextures[$alias])) return;

            $this->terrainTextures[$alias] = $childTextureName;
        }

    }

    /**
     * @return array|mixed[][]|string[]
     */
    public function getTerrainTextures(): array
    {
        return $this->terrainTextures;
    }

    /**
     * @param array<string, string|array<mixed>> $childBlockTextureAliases
     * @return void
     */
    private function mergeBlockTextureAliases(array $childBlockTextureAliases): void
    {
        foreach ($childBlockTextureAliases as $block => $childTextureAlias) {
            if (isset($this->blocks[$block])) return;

            $this->blocks[$block] = $childTextureAlias;
        }
    }

    /**
     * @return array|mixed[][]|string[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function getTextureByName(string $name): ?string
    {
        return $this->textures[$name] ?? null;
    }

    /**
     * @param string $name
     * @return string|array<mixed>|null
     */
    public function getTerrainTextureByName(string $name): string|array|null
    {
        return $this->terrainTextures[$name] ?? null;
    }

    /**
     * @param string $name
     * @return string|array<mixed>|null
     */
    public function getBlockByName(string $name): string|array|null
    {
        return $this->blocks[$name] ?? null;
    }
}