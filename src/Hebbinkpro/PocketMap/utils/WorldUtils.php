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
 * Copyright (c) 2024-2025 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\utils;

use pocketmine\block\tile\Tile;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\entity\Entity;
use pocketmine\world\biome\Biome;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\World;
use ReflectionClass;

class WorldUtils
{
    /**
     * Construct a Chunk from ChunkData
     * @param ChunkData $data the chunk data
     * @return Chunk the resulting Chunk
     */
    public static function getChunkFromData(ChunkData $data): Chunk
    {
        return new Chunk($data->getSubChunks(), $data->isPopulated());
    }

    /**
     * Get the chunk data from a chunk
     * @param World $world the world the chunk is in
     * @param int $chunkX the x pos of the chunk
     * @param int $chunkZ the z pos of the chunk
     * @param Chunk $chunk the chunk
     * @return ChunkData the chunk data
     */
    public static function getChunkData(World $world, int $chunkX, int $chunkZ, Chunk $chunk): ChunkData
    {
        return new ChunkData(
            $chunk->getSubChunks(),
            $chunk->isPopulated(),
            array_map(fn(Entity $e) => $e->saveNBT(), array_filter($world->getChunkEntities($chunkX, $chunkZ), fn(Entity $e) => $e->canSaveWithChunk())),
            array_map(fn(Tile $t) => $t->saveNBT(), $chunk->getTiles()),
        );
    }

    /**
     * Get a biome by its name
     * @param string $name
     * @return Biome|null the biome or null when it does not exist
     */
    public static function getBiomeByName(string $name): ?Biome
    {
        // get the reflector class of the biome ids
        $reflector = new ReflectionClass(BiomeIds::class);

        $id = $reflector->getConstants()[strtoupper($name)] ?? null;

        // get the biome and make sure it's ID is set!
        if ($id === null) return null;

        // get the biome and set its ID
        $biome = BiomeRegistry::getInstance()->getBiome($id);
        $biome->setId($id);

        return $biome;
    }
}