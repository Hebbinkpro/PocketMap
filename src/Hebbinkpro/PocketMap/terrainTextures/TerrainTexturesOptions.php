<?php

namespace Hebbinkpro\PocketMap\terrainTextures;

class TerrainTexturesOptions
{

    private string $fallbackBlock;
    private int $heightColor;
    private int $heightAlpha;

    public function __construct(string $fallbackBlock = "minecraft:bedrock", int $heightColor = 0x000000, int $heightAlpha = 0)
    {
        $this->fallbackBlock = $fallbackBlock;
        $this->heightColor = $heightColor;
        $this->heightAlpha = $heightAlpha;
    }

    /**
     * ID of the fallback block
     * @return string the fallback texture path or null when it doesn't exist
     */
    public function getFallbackBlock(): string
    {
        return $this->fallbackBlock;
    }

    /**
     * @return int
     */
    public function getHeightOverlayColor(): int
    {
        return $this->heightColor;
    }

    /**
     * @return int
     */
    public function getHeightOverlayAlpha(): int
    {
        return $this->heightAlpha;
    }
}