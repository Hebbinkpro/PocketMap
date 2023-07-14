<?php

namespace Hebbinkpro\PocketMap\utils;

class ResourcePack
{
    private string $path;
    private array $manifest;
    private int $textureSize;
    private array $blocks;
    private array $terrainTextures;

    public function __construct(string $path, int $textureSize)
    {
        $this->path = $path;
        $this->manifest = json_decode(file_get_contents($path . "manifest.json"), true);
        $this->textureSize = $textureSize;
        $this->blocks = json_decode(file_get_contents($path . "blocks.json"), true);
        $this->terrainTextures = json_decode(file_get_contents($path . "textures/terrain_texture.json"), true);
    }

    /**
     * Get the path
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the texture size
     * @return int
     */
    public function getTextureSize(): int
    {
        return $this->textureSize;
    }

    /**
     * Get the resource pack manifest inside the manifest.json file
     * @return array
     */
    public function getManifest(): array
    {
        return $this->manifest;
    }

    /**
     * Get the blocks inside the blocks.json file
     * @return array
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Get the terrain textures inside the texture/terrain_texture.json file
     * @return array
     */
    public function getTerrainTextures(): array
    {
        return $this->terrainTextures;
    }

}