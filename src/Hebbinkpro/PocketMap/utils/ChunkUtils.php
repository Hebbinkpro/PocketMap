<?php

namespace Hebbinkpro\PocketMap\utils;

use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\ChunkData;

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
}