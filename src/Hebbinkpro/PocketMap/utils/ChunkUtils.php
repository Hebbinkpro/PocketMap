<?php

namespace Hebbinkpro\PocketMap\utils;

use pocketmine\block\tile\Tile;
use pocketmine\entity\Entity;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\World;

class ChunkUtils
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
}