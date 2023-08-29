<?php

namespace Hebbinkpro\PocketMap\region;

use Generator;
use Hebbinkpro\PocketMap\task\AsyncRegionRenderTask;
use Hebbinkpro\PocketMap\textures\TerrainTextures;

/**
 * A normal Region, but with a generator looping through set chunks instead of all chunks that are in the region.
 */
class PartialRegion extends Region
{
    /** @var int[][] */
    private array $chunks;

    public function __construct(string $worldName, int $zoom, int $regionX, int $regionZ, TerrainTextures $terrainTextures)
    {
        parent::__construct($worldName, $zoom, $regionX, $regionZ, $terrainTextures);
        $this->chunks = [];
    }

    /**
     * Add a chunk to the chunks list
     * @param int $chunkX
     * @param int $chunkZ
     * @return void
     */
    public function addChunk(int $chunkX, int $chunkZ): void
    {
        // not inside the region
        if (!$this->isChunkInRegion($chunkX, $chunkZ)) return;

        $pos = [$chunkX, $chunkZ];
        if (!in_array($pos, $this->chunks)) {
            $this->chunks[] = [$chunkX, $chunkZ];
        }
    }

    /**
     * Remove a chunk from the list
     * @param int $chunkX
     * @param int $chunkZ
     * @return void
     */
    public function removeChunk(int $chunkX, int $chunkZ): void
    {
        $pos = [$chunkX, $chunkZ];
        $key = array_search($pos, $this->chunks);
        if (!$key) return;

        array_splice($this->chunks, $key, 1);
    }

    /**
     * Yield all chunk coordinates from the chunks in the list
     * @return Generator|array|int[]
     */
    public function getChunks(): Generator|array
    {
        // loop through all items inside the chunk list and yield the x and z position.
        foreach ($this->chunks as $pos) {
            yield $pos;
        }
    }

    public function getRenderMode(): int
    {
        return AsyncRegionRenderTask::RENDER_MODE_PARTIAL;
    }
}