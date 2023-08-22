<?php

namespace Hebbinkpro\PocketMap\render;

use Hebbinkpro\PocketMap\task\ChunkRenderTask;
use Hebbinkpro\PocketMap\task\RenderSchedulerTask;
use Hebbinkpro\PocketMap\terrainTextures\TerrainTextures;
use pocketmine\world\World;

class WorldRenderer
{
    /**
     * The size of a render in pixels
     */
    public const RENDER_SIZE = 256;

    /**
     * Zoom levels with the amount of chunks (in 1 direction) inside the zoom.
     * Large zoom value => small amount of chunks per render (high render resolution)
     * Low zoom value => Large amount of chunks per render (low render resolution)
     * @var array<integer, integer>
     */
    public const ZOOM_LEVELS = [
        -4 => 256,
        -3 => 128,
        -2 => 64,
        -1 => 32,
        0 => 16,
        1 => 8,
        2 => 4,
        3 => 2,
        4 => 1
    ];

    private World $world;
    private TerrainTextures $terrainTextures;
    private string $renderPath;
    private RenderSchedulerTask $scheduler;
    private ChunkRenderTask $chunkRenderer;

    public function __construct(World $world, TerrainTextures $terrainTextures, string $renderPath, RenderSchedulerTask $scheduler, ChunkRenderTask $chunkRenderer)
    {
        $this->world = $world;
        $this->terrainTextures = $terrainTextures;
        $this->renderPath = $renderPath;
        $this->scheduler = $scheduler;
        $this->chunkRenderer = $chunkRenderer;
    }

    /**
     * Start a complete render of the world
     * @return void
     */
    public function startFullWorldRender(): void
    {
        $this->chunkRenderer->addChunks($this, $this->world->getProvider()->getAllChunks());
    }

    /**
     * Schedule a render of the given region
     * @param Region $region
     * @param bool $force
     * @return bool
     */
    public function startRegionRender(Region $region, bool $force = false): bool
    {
        if (!is_dir($this->renderPath . $region->getZoom())) mkdir($this->renderPath . $region->getZoom());

        return $this->scheduler->scheduleRegionRender($this->renderPath, $region, $force);
    }

    /**
     * Get all regions a chunk is in
     * @param int $chunkX
     * @param int $chunkZ
     * @return Region[]
     */
    public function getAllRegionsFromChunk(int $chunkX, int $chunkZ): array
    {
        $regions = [];
        foreach (self::ZOOM_LEVELS as $zoom => $chunks) {
            $regions[] = $this->getRegionFromChunk($zoom, $chunkX, $chunkZ);
        }

        return $regions;
    }

    /**
     * Get a region from a chunk
     * @param int $zoom
     * @param int $chunkX
     * @param int $chunkZ
     * @return Region
     */
    public function getRegionFromChunk(int $zoom, int $chunkX, int $chunkZ): Region
    {
        $totalChunks = self::ZOOM_LEVELS[$zoom];
        $rx = floor($chunkX / $totalChunks);
        $rz = floor($chunkZ / $totalChunks);

        return new Region($this->getWorld()->getFolderName(), $zoom, $rx, $rz, $this->terrainTextures);
    }

    /**
     * Get the world
     * @return World
     */
    public function getWorld(): World
    {
        return $this->world;
    }

    /**
     * Get a partial region from a chunk
     * @param int $zoom
     * @param int $chunkX
     * @param int $chunkZ
     * @return PartialRegion
     */
    public function getPartialRegion(int $zoom, int $chunkX, int $chunkZ): PartialRegion
    {
        $totalChunks = self::ZOOM_LEVELS[$zoom];
        $rx = floor($chunkX / $totalChunks);
        $rz = floor($chunkZ / $totalChunks);

        return new PartialRegion($this->getWorld()->getFolderName(), $zoom, $rx, $rz, $this->terrainTextures);
    }

    /**
     * Check if there exists a render of the given region
     * @param Region $region
     * @return bool
     */
    public function hasRender(Region $region): bool
    {
        $zoom = $region->getZoom();
        $rx = $region->getX();
        $rz = $region->getZ();
        return is_file($this->renderPath . "$zoom/$rx,$rz.png");
    }

    /**
     * Get the resource pack
     * @return TerrainTextures
     */
    public function getResourcePack(): TerrainTextures
    {
        return $this->terrainTextures;
    }

    /**
     * Get the render path of this world
     * @return string
     */
    public function getRenderPath(): string
    {
        return $this->renderPath;
    }
}