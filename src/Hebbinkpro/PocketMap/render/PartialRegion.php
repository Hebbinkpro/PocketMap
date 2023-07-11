<?php

namespace Hebbinkpro\PocketMap\render;

use Generator;
use Hebbinkpro\PocketMap\task\AsyncRegionRenderTask;
use Hebbinkpro\PocketMap\utils\ResourcePack;
use pocketmine\world\format\Chunk;

class PartialRegion extends Region
{
    /** @var Chunk[] */
    private array $chunks;

    public function __construct(string $worldName, int $zoom, int $regionX, int $regionZ, ResourcePack $rp)
    {
        parent::__construct($worldName, $zoom, $regionX, $regionZ, $rp);
        $this->chunks = [];
    }

    /**
     * Add a chunk to the chunks list
     * @param Chunk $chunk
     * @param int $chunkX
     * @param int $chunkZ
     * @return void
     */
    public function addChunk(Chunk $chunk, int $chunkX, int $chunkZ): void {
        // not inside the region
        if (!$this->isChunkInRegion($chunkX, $chunkZ)) return;

        if (!array_key_exists($chunkX, $this->chunks)) $this->chunks[$chunkX] = [];
        $this->chunks[$chunkX][$chunkZ] = $chunk;
    }

    /**
     * Remove a chunk from the list
     * @param int $chunkX
     * @param int $chunkZ
     * @return void
     */
    public function removeChunk(int $chunkX, int $chunkZ): void {
        // not inside the region
        if (!$this->isChunkInRegion($chunkX, $chunkZ)) return;

        if (!array_key_exists($chunkX, $this->chunks)) return;
        unset($this->chunks[$chunkX][$chunkZ]);
    }

    /**
     * Yield all chunk coordinates from the chunks in the list
     * @return Generator|array|int[]
     */
    public function getChunks(): Generator|array
    {
        // loop through all items inside the chunk list and yield the x and z position.
        foreach ($this->chunks as $x=> $zChunks) {
            foreach ($zChunks as $z=>$chunk) {
                yield [$x,$z];
            }
        }
    }

    public function getRenderMode(): int
    {
        return AsyncRegionRenderTask::RENDER_MODE_PARTIAL;
    }
}