<?php

namespace Hebbinkpro\PocketMap\region;

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

    private int $maxChunksPerRun;

    public function __construct(Region $region, WritableWorldProvider $provider)
    {
        $this->provider = $provider;
        $this->regionChunks = RegionChunks::getEmpty($region);
        $this->chunkCoords = $region->getChunks();
        $this->finished = false;
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

        /** @var PartialRegion $partialRegion */
        $partialRegion = null;
        if ($this->regionChunks->getRegion() instanceof $partialRegion) $partialRegion = $this->regionChunks->getRegion();

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
                // remove the chunk from the partial region
                $partialRegion?->removeChunk($x, $z);
            }
            $this->chunkCoords->next();

            // the max amount of chunks in this run is reached
            if (++$i >= $this->maxChunksPerRun) break;
        }

        $this->finished = !$this->chunkCoords->valid();
        $this->regionChunks->addChunks($chunks, $this->finished);

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
}