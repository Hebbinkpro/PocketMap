<?php

namespace Hebbinkpro\PocketMap\utils;

use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\ChunkData;

class ChunkUtils
{
    public static function getChunkFromData(ChunkData $data): Chunk
    {
        return new Chunk($data->getSubChunks(), $data->isPopulated());
    }
}