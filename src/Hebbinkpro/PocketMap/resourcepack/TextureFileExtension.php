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

/**
 * All texture file extensions supported by Minecraft's texture packs.
 */
enum TextureFileExtension: string
{
    case TGA = "tga";
    case PNG = "png";
    case JPG = "jpg";
    case JPEG = "jpeg";

    /**
     * Get the priority of file extension.
     * The extension with the lowest priority value should be used.
     *
     * In case you have duplicate image references, is: .tga > .png > .jpg > .jpeg.
     *  For example: if grass.png and grass.tga both exist in the folder, grass.tga will be chosen.
     *  - Source: https://mojang.github.io/bedrock-samples/Texture%20Sets.html
     * @return int
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::TGA => 0,
            self::PNG => 1,
            self::JPG => 2,
            self::JPEG => 3,
        };
    }
}
