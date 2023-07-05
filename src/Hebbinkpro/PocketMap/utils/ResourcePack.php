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
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return int
     */
    public function getTextureSize(): int
    {
        return $this->textureSize;
    }

    /**
     * @return array
     */
    public function getManifest(): array
    {
        return $this->manifest;
    }

    /**
     * @return array
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @return array
     */
    public function getTerrainTextures(): array
    {
        return $this->terrainTextures;
    }

}