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

namespace Hebbinkpro\PocketMap\settings;

use pocketmine\utils\Config;

class TextureSettings extends ConfigSettings
{
    private string $fallback;
    private int $heightColor;
    private int $heightAlpha;

    /**
     * @param string $fallback
     * @param string $heightColor
     * @param int $heightAlpha
     */
    public function __construct(string $fallback, int $heightColor, int $heightAlpha)
    {
        $this->fallback = $fallback;
        $this->heightColor = $heightColor;
        $this->heightAlpha = $heightAlpha;
    }

    public static function fromConfig(Config $config): self
    {
        $textures = $config->get("textures");
        $fallback = strval($textures["fallback-block"]) ?? "minecraft:bedrock";

        $heightColor = intval($textures["height-overlay"]["color"] ?? 0x000000);
        $heightAlpha = intval($textures["height-overlay"]["alpha"] ?? 3);

        return new self($fallback, $heightColor, $heightAlpha);
    }

    /**
     * @return string
     */
    public function getFallback(): string
    {
        return $this->fallback;
    }

    /**
     * @return int
     */
    public function getHeightColor(): int
    {
        return $this->heightColor;
    }

    /**
     * @return int
     */
    public function getHeightAlpha(): int
    {
        return $this->heightAlpha;
    }
}