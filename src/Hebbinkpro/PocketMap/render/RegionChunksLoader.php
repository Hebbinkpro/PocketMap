<?php

namespace Hebbinkpro\PocketMap\render;

use Generator;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\utils\ChunkUtils;
use pocketmine\world\format\io\WritableWorldProvider;

class RegionChunksLoader
{
    private WritableWorldProvider $provider;
    private RegionChunks $regionChunks;
    private Generator|array $chunkCoords;
    private bool $finished;
    private array $notLoadedChunks;

    private int $maxChunksPerRun;

    public function __construct(Region $region, WritableWorldProvider $provider)
    {
        $this->provider = $provider;
        $this->regionChunks = RegionChunks::getEmpty($region);
        $this->chunkCoords = $region->getChunks();
        $this->finished = false;
        $this->notLoadedChunks = [];

        $this->maxChunksPerRun = PocketMap::getConfigManger()->getInt("renderer.chunk-loader.chunks-per-run", 128);
    }

    /**
     * Load a max of MAX_CHUNKS_PER_RUN new chunks into the region chunks instance
     * @return bool true if the region chunks instance is finished after the run
     */
    public function run(): bool
    {
        // is already completed, return true
        if ($this->finished) return true;

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
            } else {
                // the chunk data of this chunk didn't exist
                $this->notLoadedChunks[] = [$x,$z];
            }
            $this->chunkCoords->next();

            // the max amount of chunks in this run is reached
            if (++$i >= $this->maxChunksPerRun) break;
        }

        $this->finished = !$this->chunkCoords->valid();
        $this->regionChunks = RegionChunks::addChunks($this->regionChunks, $chunks, $this->finished);

        return $this->finished;
    }

    /**
     * Get if the loader is finished
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->finished;
    }

    /**
     * Get the region chunks that are loaded
     * @return RegionChunks
     */
    public function getRegionChunks(): RegionChunks
    {
        return $this->regionChunks;
    }

    /**
     * Get all chunks that are not loaded
     * @return array
     */
    public function getNotLoadedChunks(): array {
        return $this->notLoadedChunks;
    }
}