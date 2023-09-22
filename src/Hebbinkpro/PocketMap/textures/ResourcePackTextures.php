<?php

namespace Hebbinkpro\PocketMap\textures;

use Hebbinkpro\PocketMap\utils\ResourcePackUtils;

class ResourcePackTextures
{
    /** @var array<string, string> List containing all textures inside /textures/blocks/ */
    protected array $textures;
    /** @var array<string, string|array> List of all texture aliases mapping a texture defined in /textures/terrain_texture.json */
    protected array $terrainTextures;
    /** @var array<string, string|array> List of all blocks mapping a texture alias defined in /blocks.json */
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
        if (!empty($prefix)) $prefix .= "/";

        $this->textures = [];

        foreach (scandir($path) as $block) {
            $blocks = [$block];

            if (is_dir($path . $block)) {
                $blocks = scandir($path . $block);
                // if there is for some reason another dir in this list, it's the texture pack makers own fault.
                // It will probably not break anything.
            } else if (!is_file($path . $block)) continue;

            foreach ($blocks as $file) {
                if (str_ends_with($file, ".png") || str_ends_with($file, ".tga")) {
                    $name = str_replace([".png", ".tga"], "", $file);
                    if (!array_key_exists($name, $this->textures)) {
                        $this->textures[$name] = $prefix . ResourcePackUtils::BLOCK_TEXTURES . $name;
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

        $contents = json_decode(file_get_contents($path), true) ?? [];
        if (!isset($contents["texture_data"])) {
            return;
        }

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

                $this->terrainTextures[$name] = [];
                foreach ($texture as $key => $item) {
                    $textureName = null;
                    // name: [texture, ...]
                    if (is_string($item)) $textureName = self::getTextureName($item);
                    // name: [{path: texture}, ...]
                    if (is_array($item) && isset($item["path"])) $textureName = self::getTextureName($item["path"]);

                    if ($textureName !== null && self::isValidTexture($textureName)) {
                        $this->terrainTextures[$name][$key] = $textureName;
                    }
                }

                // remove empty list
                if (empty($this->terrainTextures[$name])) unset($this->terrainTextures[$name]);
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

        $contents = json_decode(file_get_contents($path), true) ?? [];

        $this->blocks = [];
        foreach ($contents as $name => $data) {
            if (!isset($data["textures"])) continue;
            $texture = $data["textures"];

            if (is_string($texture) && self::isValidTextureAlias($texture)) {
                $this->blocks[$name] = $texture;
            }

            if (is_array($texture)) {
                $this->blocks[$name] = [];
                foreach ($texture as $direction => $directionAlias) {
                    if (self::isValidTextureAlias($directionAlias)) {
                        $this->blocks[$name][$direction] = $directionAlias;
                    }
                }

                // remove empty list
                if (empty($this->blocks[$name])) unset($this->blocks[$name]);
            }
        }


    }

    public function isValidTextureAlias(string $alias): bool
    {
        return array_key_exists($alias, $this->terrainTextures);
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

    public function getTexture(string $name): ?string
    {
        return $this->textures[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getTextures(): array
    {
        return $this->textures;
    }

    private function mergeTextureAliases(array $childTextureAliases): void
    {

        foreach ($childTextureAliases as $alias => $childTextureName) {
            if (isset($this->terrainTextures[$alias])) return;

            $this->terrainTextures[$alias] = $childTextureName;
        }

    }

    /**
     * @return array
     */
    public function getTerrainTextures(): array
    {
        return $this->terrainTextures;
    }

    private function mergeBlockTextureAliases(array $childBlockTextureAliases): void
    {
        foreach ($childBlockTextureAliases as $block => $childTextureAlias) {
            if (isset($this->blocks[$block])) return;

            $this->blocks[$block] = $childTextureAlias;
        }
    }

    /**
     * @return array
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function getTextureByName(string $name): ?string
    {
        return $this->textures[$name] ?? null;
    }

    public function getTerrainTextureByName(string $name): string|array|null
    {
        return $this->terrainTextures[$name] ?? null;
    }

    public function getBlockByName(string $name): string|array|null
    {
        return $this->blocks[$name] ?? null;
    }
}