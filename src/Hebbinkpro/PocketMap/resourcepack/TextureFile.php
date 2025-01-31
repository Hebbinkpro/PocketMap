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

use GdImage;

class TextureFile
{
    private string $name;
    private string $path;
    private TextureFileExtension $extension;

    /**
     * Create a texture file
     * @param string $name the name of the file
     * @param string $path the path of the file
     * @param TextureFileExtension $extension the file extension
     */
    public function __construct(string $name, string $path, TextureFileExtension $extension)
    {
        $this->name = $name;
        $this->path = $path;
        $this->extension = $extension;
    }

    /**
     * Get all texture files inside the given directory.
     *
     * If duplicate texture names exist, the file with the highest extension priority will be used.
     *      (see `Hebbinkpro\PocketMap\rp\TextureFileExtension` for more info about extension priorities)
     * @param string $dir the directory
     * @param bool $recursive if textures in subdirectories should be included
     * @param array<string, TextureFile> $textures list of already extracted textures with their names,
     *                                     with which the textures inside the dir should be merged
     * @return array<string, TextureFile> list of all texture files mapped by their name
     */
    public static function getTexturesFromDir(string $dir, bool $recursive = true, array $textures = []): array
    {
        if (!is_dir($dir) || ($files = scandir($dir)) === false) return [];

        foreach ($files as $file) {
            if ($file === "." || $file === "..") continue;

            $path = $dir . $file;
            if ($recursive && is_dir($path)) {
                // set the updated textures
                $textures = self::getTexturesFromDir($path . "/", $recursive, $textures);
                continue;
            }

            // get the texture from the path
            if (($texture = TextureFile::fromPath($path)) !== null) {
                $name = $texture->getName();

                // there exists a duplicate texture with a lower priority
                if (isset($textures[$name]) &&
                    $textures[$name]->getExtension()->getPriority() < $texture->getExtension()->getPriority()) {
                    continue;
                }

                // set the texture
                $textures[$name] = $texture;
            }
        }

        return $textures;
    }

    /**
     * Create a texture file from the given path
     * @param string $path the path to the texture file
     * @return self|null the new texture file, or null when invalid
     */
    public static function fromPath(string $path): ?self
    {
        $fileName = basename($path);

        // should result in [name, ext]
        $parts = explode('.', $fileName);
        if (sizeof($parts) < 2) return null;

        $name = $parts[0];
        $ext = TextureFileExtension::tryFrom($parts[1]);

        // invalid extension
        if ($ext === null) return null;

        return new self($name, $path, $ext);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get if this file has a lower priority than the given file
     * @param TextureFile $file
     * @return bool true if the extension of this file is lower than the extension of the given file
     */
    public function hasLowerPriority(TextureFile $file): bool
    {
        return $this->extension->getPriority() <= $file->getExtension()->getPriority();
    }

    /**
     * @return TextureFileExtension
     */
    public function getExtension(): TextureFileExtension
    {
        return $this->extension;
    }

    /**
     * Create a GD Image from the texture file path
     * @return GdImage
     */
    public function createImage(): GdImage
    {
        return match ($this->extension) {
            TextureFileExtension::TGA => imagecreatefromtga($this->path),
            TextureFileExtension::PNG => imagecreatefrompng($this->path),
            TextureFileExtension::JPG, TextureFileExtension::JPEG => imagecreatefromjpeg($this->path),
        };
    }
}