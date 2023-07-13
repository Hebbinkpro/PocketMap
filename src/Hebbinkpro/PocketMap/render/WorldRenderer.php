<?php

namespace Hebbinkpro\PocketMap\render;

use Hebbinkpro\PocketMap\task\AsyncRegionRenderTask;
use Hebbinkpro\PocketMap\task\RenderSchedulerTask;
use Hebbinkpro\PocketMap\utils\ResourcePack;
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
    private ResourcePack $rp;
    private string $renderPath;
    private RenderSchedulerTask $scheduler;

    public function __construct(World $world, ResourcePack $rp, string $renderPath, RenderSchedulerTask $scheduler)
    {
        $this->world = $world;
        $this->rp = $rp;
        $this->renderPath = $renderPath;
        $this->scheduler = $scheduler;
    }

    public function startFullWorldRender(): void
    {
        $this->scheduler->scheduleFullWorldRender($this);
    }

    /**
     * @param int $zoom
     * @return void
     */
    public function startZoomRender(int $zoom): void
    {
        $totalChunks = self::ZOOM_LEVELS[$zoom];

        $loadedRegions = [];

        foreach ($this->world->getProvider()->getAllChunks() as $coords => $chunkData) {
            [$cx, $cz] = $coords;

            // region coords
            $rx = floor($cx / $totalChunks);
            $rz = floor($cz / $totalChunks);

            // already did this region
            if (in_array([$rx, $rz], $loadedRegions)) continue;

            $region = new Region($this->world->getFolderName(), $zoom, $rx, $rz, $this->rp);
            $this->startRegionRender($region, true);
            $loadedRegions[] = [$rx, $rz];
        }
    }

    public function startRegionRender(Region $region, bool $force = false): bool
    {
        if (!is_dir($this->renderPath . $region->getZoom())) mkdir($this->renderPath . $region->getZoom());

        return $this->scheduler->scheduleRegionRender($this->renderPath, $region, $force);
    }

    /**
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

    public function getRegionFromChunk(int $zoom, int $chunkX, int $chunkZ): Region
    {
        $totalChunks = self::ZOOM_LEVELS[$zoom];
        $rx = floor($chunkX / $totalChunks);
        $rz = floor($chunkZ / $totalChunks);

        return new Region($this->getWorld()->getFolderName(), $zoom, $rx, $rz, $this->rp);
    }

    public function getPartialRegion(int $zoom, int $chunkX, int $chunkZ): PartialRegion
    {
        $totalChunks = self::ZOOM_LEVELS[$zoom];
        $rx = floor($chunkX / $totalChunks);
        $rz = floor($chunkZ / $totalChunks);

        return new PartialRegion($this->getWorld()->getFolderName(), $zoom, $rx, $rz, $this->rp);
    }

    /**
     * @return World
     */
    public function getWorld(): World
    {
        return $this->world;
    }

    public function hasRender(Region $region): bool
    {
        $zoom = $region->getZoom();
        $rx = $region->getRegionX();
        $rz = $region->getRegionZ();
        return is_file($this->renderPath . "$zoom/$rx,$rz.png");
    }

    /**
     * @return ResourcePack
     */
    public function getRp(): ResourcePack
    {
        return $this->rp;
    }

    /**
     * @return string
     */
    public function getRenderPath(): string
    {
        return $this->renderPath;
    }
}