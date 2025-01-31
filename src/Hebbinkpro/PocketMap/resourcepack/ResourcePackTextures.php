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

class ResourcePackTextures
{

    /** @var array<string, TextureFile> */
    private array $blocks;
    /** @var array<string, TextureFile> */
    private array $colorMaps;
    /** @var array<string, TextureFile> */
    private array $entities;

    /**
     * @param array<string, TextureFile> $blocks
     * @param array<string, TextureFile> $colorMaps
     * @param array<string, TextureFile> $entities
     */
    public function __construct(array $blocks, array $colorMaps, array $entities)
    {
        $this->blocks = $blocks;
        $this->colorMaps = $colorMaps;
        $this->entities = $entities;
    }

    /**
     * Get an instance without textures
     * @return ResourcePackTextures
     */
    public static function empty(): ResourcePackTextures
    {
        return new ResourcePackTextures([], [], []);
    }

    /**
     * Add a block texture
     * @param TextureFile $block
     * @return void
     */
    public function addBlock(TextureFile $block): void
    {
        $name = $block->getName();
        // there already is a block with a lower priority
        if (isset($this->blocks[$name]) &&
            $block->hasLowerPriority($this->blocks[$name])) {
            return;
        }

        $this->blocks[$name] = $block;
    }

    /**
     * Add a color map texture
     * @param TextureFile $colorMap
     * @return void
     */
    public function addColorMap(TextureFile $colorMap): void
    {
        $name = $colorMap->getName();
        // there already is a block with a lower priority
        if (isset($this->colorMaps[$name]) &&
            $colorMap->hasLowerPriority($this->colorMaps[$name])) {
            return;
        }

        $this->colorMaps[$name] = $colorMap;
    }

    /**
     * Add an entity texture
     * @param TextureFile $entity
     * @return void
     */
    public function addEntity(TextureFile $entity): void
    {
        $name = $entity->getName();
        // there already is a block with a lower priority
        if (isset($this->entities[$name]) &&
            $entity->hasLowerPriority($this->entities[$name])) {
            return;
        }

        $this->entities[$name] = $entity;
    }

    /**
     * Merge resource pack textures.
     *
     * The given resource pack can overwrite textures of the current resource pack.
     * @param ResourcePackTextures $textures
     * @return ResourcePackTextures a new resource pack textures instance with the merged values
     */
    public function merge(ResourcePackTextures $textures): ResourcePackTextures
    {
        return new ResourcePackTextures(
            array_merge($this->blocks, $textures->getBlocks()),
            array_merge($this->colorMaps, $textures->getColorMaps()),
            array_merge($this->entities, $textures->getEntities())
        );
    }

    /**
     * @return array<string, TextureFile>
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @return array<string, TextureFile>
     */
    public function getColorMaps(): array
    {
        return $this->colorMaps;
    }

    /**
     * @return array<string, TextureFile>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

}