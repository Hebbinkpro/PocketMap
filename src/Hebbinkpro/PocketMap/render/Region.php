<?php

namespace Hebbinkpro\PocketMap\render;

use Generator;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\task\AsyncRegionRenderTask;
use Hebbinkpro\PocketMap\utils\ResourcePack;

class Region
{
    private string $worldName;
    private int $zoom;
    private int $regionX;
    private int $regionZ;
    private ResourcePack $rp;
    private string $tmpFile;


    public function __construct(string $worldName, int $zoom, int $regionX, int $regionZ, ResourcePack $rp)
    {
        $this->worldName = $worldName;
        $this->zoom = $zoom;
        $this->regionX = $regionX;
        $this->regionZ = $regionZ;
        $this->rp = $rp;
        $this->tmpFile = PocketMap::getTmpDataPath()."regions/$this->worldName/$this->zoom,$this->regionX,$this->regionZ.json";
    }

    /**
     * Yields all chunk coordinates that are inside the region
     * @return int[]|Generator
     */
    public function getChunks(): Generator|array
    {
        $minX = $this->regionX * $this->getTotalChunks();
        $minZ = $this->regionZ * $this->getTotalChunks();

        for ($x = $minX; $x < $minX + $this->getTotalChunks(); $x++) {
            for ($z = $minZ; $z < $minZ + $this->getTotalChunks(); $z++) {
                yield [$x, $z];
            }
        }
    }

    public function getTotalChunks(): int
    {
        return WorldRenderer::ZOOM_LEVELS[$this->zoom];
    }

    /**
     * @return ResourcePack
     */
    public function getResourcePack(): ResourcePack
    {
        return $this->rp;
    }

    /**
     * Get the coordinates of a chunk inside the region.
     * @param int $worldChunkX the x coordinate of the chunk inside the world
     * @param int $worldChunkZ the z coordinate of the chunk inside the world
     * @return int[]
     */
    public function getRegionChunkCoords(int $worldChunkX, int $worldChunkZ): array
    {
        return [
            $worldChunkX - ($this->regionX * $this->getTotalChunks()),
            $worldChunkZ - ($this->regionZ * $this->getTotalChunks())
        ];
    }

    /**
     * Check if a chunk is inside the region.
     * @param int $chunkX
     * @param int $chunkZ
     * @return bool
     */
    public function isChunkInRegion(int $chunkX, int $chunkZ): bool {
        $minX = $this->regionX * $this->getTotalChunks();
        $minZ = $this->regionZ * $this->getTotalChunks();
        $maxX = ($this->regionX + 1) * $this->getTotalChunks();
        $maxZ = ($this->regionZ + 1) * $this->getTotalChunks();

        return $minX <= $chunkX && $chunkX < $maxX
            && $minZ <= $chunkZ && $chunkZ < $maxZ;
    }

    /**
     * Get the coordinates of a chunk inside the world
     * @param int $regionChunkX the x coordinate of the chunk inside the region
     * @param int $regionChunkZ the z coordinate of the chunk inside the region
     * @return float[]|int[]
     */
    public function getWorldChunkCoords(int $regionChunkX, int $regionChunkZ): array
    {
        return [
            $this->regionX * $this->getTotalChunks() + $regionChunkX,
            $this->regionZ * $this->getTotalChunks() + $regionChunkZ
        ];
    }

    public function getPixelsPerBlock(): int
    {
        return WorldRenderer::getPixelsPerBlock($this->zoom, $this->rp);
    }

    public function getChunkPixelSize(): int
    {
        return floor(WorldRenderer::RENDER_SIZE / $this->getTotalChunks());
    }

    /**
     * Compares the region with a given region.
     * @param Region $region the region to compare with
     * @return bool true if the region is the same, false otherwise
     */
    public function equals(Region $region): bool
    {
        return $region->getWorldName() === $this->worldName &&
            $region->getZoom() == $this->zoom &&
            $region->getRegionX() == $this->regionX &&
            $region->getRegionZ() == $this->regionZ;
    }

    /**
     * @return string
     */
    public function getWorldName(): string
    {
        return $this->worldName;
    }

    /**
     * @return int
     */
    public function getZoom(): int
    {
        return $this->zoom;
    }

    /**
     * @return int
     */
    public function getRegionX(): int
    {
        return $this->regionX;
    }

    /**
     * @return int
     */
    public function getRegionZ(): int
    {
        return $this->regionZ;
    }

    public function getRenderMode(): int {
        return AsyncRegionRenderTask::RENDER_MODE_FULL;
    }

    /**
     * @return array{completed: bool, chunks?: int[][]}|null
     */
    public function getRenderData(): ?array {
        try {
            $fileData = file_get_contents($this->tmpFile);
        } catch (\Exception $e) {
            return null;
        }

        $data = json_decode($fileData, true);
        if (!$data) return null;
        return $data;
    }

    public function isRenderDataComplete(): bool {
        $data = $this->getRenderData();
        if ($data === null) return false;
        return $data["completed"] ?? false;
    }

    public function addChunkToRenderData(int $chunkX, int $chunkZ): void {
        // cannot add chunk
        if ($this->isRenderDataComplete() || !$this->isChunkInRegion($chunkX, $chunkZ)) return;

        // get the data
        $data = $this->getRenderData();
        if ($data === null) {
            $data = [
                "completed" => false,
                "chunks" => []
            ];
        }

        $storedChunks = $data["chunks"];

        // x pos does not yet exist
        if (!array_key_exists($chunkX, $storedChunks)) $storedChunks[$chunkX] = [];

        // chunk already stored
        if (in_array($chunkZ, $storedChunks[$chunkX])) return;

        $storedChunks[$chunkX][] = $chunkZ;

        // the region is completed
        if (sizeof($storedChunks) >= $this->getTotalChunks()) {
            // mark region as completed
            $data["completed"] = true;
            // remove the redundant data
            unset($data["chunks"]);
        } else {
            // set the new chunks
            $data["chunks"] = $storedChunks;
        }

        // store the data
        file_put_contents($this->tmpFile, json_encode($data));
    }

    public function hasRenderDataChunk(int $chunkX, int $chunkZ): bool {
        if (!$this->isChunkInRegion($chunkX, $chunkZ)) {
            var_dump("Stupid not in region: $chunkX,$chunkZ");
            return false;
        }
        if ($this->isRenderDataComplete()) return true;

        $data = $this->getRenderData() ?? [];

        if (!array_key_exists("chunks", $data)) return false;
        if (!array_key_exists($chunkX, $data["chunks"])) return false;
        return in_array($chunkZ, $data["chunks"][$chunkX]);
    }
}