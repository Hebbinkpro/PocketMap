<?php

namespace Hebbinkpro\PocketMap\render;

use Generator;
use Hebbinkpro\PocketMap\utils\ChunkUtils;
use pocketmine\world\format\io\WritableWorldProvider;

class RegionChunksLoader
{
    public const MAX_CHUNKS_PER_RUN = 128;

    private WritableWorldProvider $provider;
    private RegionChunks $regionChunks;
    private Generator|array $chunkCoords;

    public function __construct(Region $region, WritableWorldProvider $provider)
    {
        $this->provider = $provider;
        $this->regionChunks = RegionChunks::getEmpty($region);

        $this->chunkCoords = $region->getChunks();
    }

    /**
     * Load a max of MAX_CHUNKS_PER_RUN new chunks into the region chunks instance
     * @return bool true if the region chunks instance is complete after the run
     */
    public function run(): bool
    {

        $i = 0;
        $chunks = [];

        // loop through all chunk coords
        while ($this->chunkCoords->valid()) {

            [$x, $z] = $this->chunkCoords->current();

            //load the chunk
            $chunkData = $this->provider->loadChunk($x, $z);
            if ($chunkData !== null) {
                if (!array_key_exists($x, $chunks)) $chunks[$x] = [];
                $chunks[$x][$z] = ChunkUtils::getChunkFromData($chunkData->getData());
            }
            $this->chunkCoords->next();

            // the max amount of chunks in this run is reached
            if (++$i >= self::MAX_CHUNKS_PER_RUN) break;
        }

        $completed = !$this->chunkCoords->valid();
        $this->regionChunks = RegionChunks::addChunks($this->regionChunks, $chunks, $completed);
        return $completed;
    }

    /**
     * @return RegionChunks
     */
    public function getRegionChunks(): RegionChunks
    {
        return $this->regionChunks;
    }
}