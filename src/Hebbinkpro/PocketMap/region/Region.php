<?php

namespace Hebbinkpro\PocketMap\region;

use Generator;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\textures\TerrainTextures;

class Region
{
    private string $worldName;
    private int $zoom;
    private int $x;
    private int $z;
    private TerrainTextures $terrainTextures;


    public function __construct(string $worldName, int $zoom, int $regionX, int $regionZ, TerrainTextures $terrainTextures)
    {
        $this->worldName = $worldName;
        $this->zoom = $zoom;
        $this->x = $regionX;
        $this->z = $regionZ;
        $this->terrainTextures = $terrainTextures;
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
     * Get the terrain textures used for rendering the region
     * @return TerrainTextures
     */
    public function getTerrainTextures(): TerrainTextures
    {
        return $this->terrainTextures;
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
     * Get the region name in the format: world/zoom/x,z
     * @param bool $includeWorld if the world is included in the name
     * @return string
     */
    public function getName(bool $includeWorld = true): string
    {
        $region = $this->getZoom() . "/" . $this->getX() . "," . $this->getZ();
        if (!$includeWorld) return $region;
        return $this->getWorldName() . "/" . $region;
    }

    /**
     * Get if the region only exists out of a single chunk.
     * @return bool
     */
    public function isChunk(): bool
    {
        return $this->getTotalChunks() == 1;
    }
}