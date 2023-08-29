<?php

namespace Hebbinkpro\PocketMap\textures;

use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;

class TerrainTexturesOptions
{

    private string $fallbackBlock;
    private int $heightColor;
    private int $heightAlpha;

    public function __construct(?Block $fallbackBlock = null, int $heightColor = 0x000000, int $heightAlpha = 0)
    {
        if ($fallbackBlock === null) $fallbackBlock = VanillaBlocks::BEDROCK();

        $this->fallbackBlock = TextureUtils::getBlockTextureName($fallbackBlock);
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