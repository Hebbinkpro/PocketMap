<?php

namespace Hebbinkpro\PocketMap\render;

use Exception;
use Generator;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\task\AsyncRegionRenderTask;
use Hebbinkpro\PocketMap\utils\ArrayUtils;
use Hebbinkpro\PocketMap\utils\ResourcePack;

class Region
{
    private string $worldName;
    private int $zoom;
    private int $x;
    private int $z;
    private ResourcePack $rp;
    private string $tmpFile;


    public function __construct(string $worldName, int $zoom, int $regionX, int $regionZ, ResourcePack $rp)
    {
        $this->worldName = $worldName;
        $this->zoom = $zoom;
        $this->x = $regionX;
        $this->z = $regionZ;
        $this->rp = $rp;

        $largestRegion = $this->getLargestZoomRegion();
        $this->tmpFile = PocketMap::getTmpDataPath() . "regions/$this->worldName/{$largestRegion["x"]},{$largestRegion["z"]}.json";
    }

    /**
     * Get the info from the region with the highest zoom level this region is in
     * @return array{zoom: int, chunks: int, x: int, z: int} the zoom, amount of chunks, x pos and z pos of the region
     */
    public function getLargestZoomRegion(): array
    {
        $zoom = array_key_first(WorldRenderer::ZOOM_LEVELS);
        $chunks = WorldRenderer::ZOOM_LEVELS[$zoom];

        $x = $this->x;
        $z = $this->z;

        if ($this->zoom > $zoom) {
            $diff = $chunks / $this->getTotalChunks();

            $x = floor($x / $diff);
            $z = floor($z / $diff);
        }
        return [
            "zoom" => $zoom,
            "chunks" => $chunks,
            "x" => $x,
            "z" => $z
        ];
    }

    /**
     * Get the total amount of chunks inside the region
     * @return int
     */
    public function getTotalChunks(): int
    {
        return WorldRenderer::ZOOM_LEVELS[$this->zoom];
    }

    /**
     * Yields all chunk coordinates that are inside the region
     * @return int[]|Generator
     */
    public function getChunks(): Generator|array
    {
        $minX = $this->x * $this->getTotalChunks();
        $minZ = $this->z * $this->getTotalChunks();

        for ($x = $minX; $x < $minX + $this->getTotalChunks(); $x++) {
            for ($z = $minZ; $z < $minZ + $this->getTotalChunks(); $z++) {
                yield [$x, $z];
            }
        }
    }

    /**
     * Get the resource pack used for rendering the region
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
            $worldChunkX - ($this->x * $this->getTotalChunks()),
            $worldChunkZ - ($this->z * $this->getTotalChunks())
        ];
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
            $this->x * $this->getTotalChunks() + $regionChunkX,
            $this->z * $this->getTotalChunks() + $regionChunkZ
        ];
    }

    /**
     * Get the pixel size of a chunk inside a render of this region
     * @return int
     */
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
            $region->getX() == $this->x &&
            $region->getZ() == $this->z;
    }

    /**
     * Get the world name of the region
     * @return string
     */
    public function getWorldName(): string
    {
        return $this->worldName;
    }

    /**
     * Get the zoom level of the region
     * @return int
     */
    public function getZoom(): int
    {
        return $this->zoom;
    }

    /**
     * Get the X position of the region
     * @return int
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * Get the Z position of the region
     * @return int
     */
    public function getZ(): int
    {
        return $this->z;
    }

    /**
     * Get the render mode
     * @return int
     */
    public function getRenderMode(): int
    {
        return AsyncRegionRenderTask::RENDER_MODE_FULL;
    }

    /**
     * Add a chunk to the list in the render data of the render with the highest zoom level
     * @param int[][] $chunks
     * @return void
     * @throws Exception
     */
    public function addChunksToRenderData(array $chunks): void
    {
        // cannot add chunk
        if ($this->isRenderDataComplete()) return;

        $renderData = $this->loadRenderData();

        foreach ($chunks as [$cx,$cz]) {
            if (!$this->isChunkInRegion($cx, $cz)) continue;

            if (!array_key_exists("$cx", $renderData["chunks"])) $renderData["chunks"]["$cx"] = [];

            if (!in_array($cz, $renderData["chunks"]["$cx"])) {
                $renderData["chunks"]["$cx"][] = $cz;
            }

        }

        $largestRegion = $this->getLargestZoomRegion();
        $totalChunks = $largestRegion["chunks"];

        // the region is completed
        if ($this->getChunksInRenderData($renderData) >= pow($totalChunks, 2)) {
            // mark region as completed
            $renderData["completed"] = true;
            // remove the redundant data
            $renderData["chunks"] = [];
        }

        file_put_contents($this->tmpFile, json_encode($renderData));
    }

    /**
     * Get if the render data if complete
     * @return bool
     */
    public function isRenderDataComplete(): bool
    {
        $data = $this->loadRenderData();
        return $data["completed"] ?? false;
    }

    public function getChunksInRenderData(array $renderData): int {
        $amount = 0;

        foreach ($renderData["chunks"] as $x=>$chunks) {
            $amount += count($renderData["chunks"]["$x"]);
        }

        return $amount;
    }

    private function loadRenderData(): array {
        try {
            $fileData = file_get_contents($this->tmpFile);
            $data = json_decode($fileData, true);
            if (!$data) $data = ["completed" => false, "chunks" => []];
        } catch (Exception $e) {
            $data = ["completed" => false, "chunks" => []];
        }

        return $data;
    }


    /**
     * Check if a chunk is inside the region.
     * @param int $chunkX
     * @param int $chunkZ
     * @return bool
     */
    public function isChunkInRegion(int $chunkX, int $chunkZ): bool
    {
        $minX = $this->x * $this->getTotalChunks();
        $minZ = $this->z * $this->getTotalChunks();
        $maxX = ($this->x + 1) * $this->getTotalChunks();
        $maxZ = ($this->z + 1) * $this->getTotalChunks();

        return $minX <= $chunkX && $chunkX < $maxX
            && $minZ <= $chunkZ && $chunkZ < $maxZ;
    }

    /**
     * Get if the given chunk is inside the render data
     * @param int $chunkX the x pos of the chunk
     * @param int $chunkZ the z pos of the chunk
     * @return bool if the chunk is inside the render data
     */
    public function isChunkInRenderData(int $chunkX, int $chunkZ): bool
    {
        if (!$this->isChunkInRegion($chunkX, $chunkZ)) {
            return false;
        }
        if ($this->isRenderDataComplete()) return true;

        $data = $this->loadRenderData() ?? [];

        if (!array_key_exists("chunks", $data)) return false;
        if (!array_key_exists($chunkX, $data["chunks"])) return false;
        return in_array($chunkZ, $data["chunks"]["$chunkX"]);
    }

    public function __toString(): string {
        return $this->getZoom()."/".$this->getX().",".$this->getZ();
    }
}