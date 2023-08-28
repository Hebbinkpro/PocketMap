<?php

namespace Hebbinkpro\PocketMap\render;

use Generator;
use Hebbinkpro\PocketMap\utils\ArrayUtils;
use Hebbinkpro\PocketMap\utils\ChunkUtils;
use pocketmine\world\format\Chunk;
use pocketmine\world\WorldManager;

/**
 * Class that contains chunk information of all chunks inside a region.
 * The chunk information can be encoded and decoded in and from a string,
 * this allows the data to be transferred to an async thread
 */
class RegionChunks
{
    private Region $region;
    /** @var Chunk[][] */
    private array $chunks;
    private bool $completed;

    /**
     * The RegionChunks becomes invalid when $this->encode() is called, $this->chunks will be cleared.
     * Because of this, this class will become unusable.
     * @var bool
     */
    private bool $valid;

    private function __construct(Region $region, array $chunks = [], bool $completed = false)
    {
        $this->region = $region;
        $this->chunks = $chunks;
        $this->completed = $completed;
        $this->valid = true;
    }

    /**
     * Create a region chunks instance and immediately loads all chunks into the instance
     * @param Region $region
     * @param WorldManager $wm
     * @return RegionChunks|null
     */
    public static function getCompleted(Region $region, WorldManager $wm): ?RegionChunks
    {
        // load the world of the region
        if (!$wm->isWorldLoaded($region->getWorldName())) $wm->loadWorld($region->getWorldName());
        $world = $wm->getWorldByName($region->getWorldName());

        // the world the region is in does not exist!
        if ($world === null) return null;


        $provider = $world->getProvider();
        $chunks = [];

        foreach ($region->getChunks() as [$x, $z]) {
            //load the chunk
            $chunkData = $provider->loadChunk($x, $z);
            if ($chunkData === null) {
                continue;
            }

            if (!array_key_exists($x, $chunks)) $chunks[$x] = [];
            $chunks[$x][$z] = ChunkUtils::getChunkFromData($chunkData->getData());
        }

        return new RegionChunks($region, $chunks, true);
    }

    /**
     * Get all the loaded chunks
     * @return array
     */
    public function getChunks(): array
    {
        return $this->chunks;
    }

    /**
     * Create an empty region chunks instance
     * @param Region $region
     * @return RegionChunks
     */
    public static function getEmpty(Region $region): RegionChunks
    {
        return new RegionChunks($region);
    }

    /**
     * Yield all chunks from an encoded region chunks instance
     * @param string $encodedData
     * @return Generator|array{int, int, Chunk}
     */
    public static function yieldAllEncodedChunks(string $encodedData): Generator|array
    {
        /** @var array{chunks: string[][]} $data */
        $data = unserialize($encodedData);

        foreach ($data["chunks"] as $dx => $dzChunks) {
            foreach ($dzChunks as $dz => $chunkData) {
                yield ([$dx, $dz, unserialize($chunkData)]);
                unset($data["chunks"][$dx][$dz]);
            }
        }
    }

    /**
     * Add chunks to an uncompleted region chunks instance
     * @param Chunk[][] $chunks the chunks to merge with the region
     * @param bool $completed if the region is completed after this merge
     * @return bool false when the region was already completed or when it is invalid
     */
    public function addChunks(array $chunks, bool $completed = false): bool
    {
        // cannot add chunks to an already completed region chunks instance
        if ($this->completed || !$this->valid) return false;

        // merge the chunks from the region chunks instance and the new chunks together
        $this->chunks = ArrayUtils::merge($this->chunks, $chunks);
        $this->completed = $completed;


        return true;
    }

    /**
     * Get if the chunks list is completed
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->completed;
    }

    /**
     * If this instance is still valid
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get the region of the chunks
     * @return Region
     */
    public function getRegion(): Region
    {
        return $this->region;
    }

    /**
     * Encodes all chunks inside this region chunks instance
     * @return string
     */
    public function encode(): string
    {
        $chunkData = [];
        foreach ($this->chunks as $dx => $dzChunks) {
            $chunkData[$dx] = [];
            foreach ($dzChunks as $dz => $chunk) {
                $chunkData[$dx][$dz] = serialize($chunk);
                unset($this->chunks[$dx][$dz]);
            }
        }

        // destroy the chunk cache from the memory, WE NEED SPACE
        unset($this->chunks);
        $this->valid = false;

        return serialize([
            "chunks" => $chunkData
        ]);
    }
}