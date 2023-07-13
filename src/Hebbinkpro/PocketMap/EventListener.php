<?php

namespace Hebbinkpro\PocketMap;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockMeltEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockTeleportEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\Listener;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\world\Position;


class EventListener implements Listener
{
    private PocketMap $plugin;

    public function __construct(PocketMap $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onWorldLoad(WorldLoadEvent $e): void
    {
        // create a world renderer for the loaded world
        $renderer = $this->plugin->createWorldRenderer($e->getWorld());
        $this->plugin->getLogger()->debug("Created renderer for world: " . $e->getWorld()->getFolderName());

        // check if the render folder for the world is empty
        if (count(scandir($renderer->getRenderPath())) <= 2) {
            // the renders folder is empty
            // load the full world
            $renderer->startFullWorldRender();
        }
    }

    public function onWorldUnload(WorldUnloadEvent $e): void
    {
        // remove the renderer from the world
        $this->plugin->removeWorldRenderer($e->getWorld());
        $this->plugin->getLogger()->debug("Destroyed renderer for world: " . $e->getWorld()->getFolderName());
    }

    public function onChunkLoad(ChunkLoadEvent $e)
    {
        $world = $e->getWorld();
        $cx = $e->getChunkX();
        $cz = $e->getChunkZ();

        // get the world renderer
        $renderer = $this->plugin->getWorldRenderer($world->getFolderName());

        // get all regions the chunk exists in
        $regions = $renderer->getAllRegionsFromChunk($cx, $cz);


        foreach ($regions as $region) {
            // chunk already rendered
            if ($region->hasRenderDataChunk($cx, $cz)) continue;
            $this->plugin->getLogger()->debug("Found a not rendered chunk: $cx,$cz in world: " . $world->getFolderName() . " for zoom: " . $region->getZoom());

            // chunk is not yet rendered
            $chunk = $e->getChunk();
            // if it doesn't have a render, render all regions{
            $this->plugin->getChunkUpdateTask()->addChunk($world, $chunk, $cx, $cz);
        }
    }

    public function onBlockBreak(BlockBreakEvent $e): void
    {
        $this->blockUpdate($e->getBlock()->getPosition());
    }

    private function blockUpdate(Position $pos): void
    {
        $world = $pos->getWorld();
        $chunkX = floor($pos->getX() / 16);
        $chunkZ = floor($pos->getZ() / 16);

        // get the chunk
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if ($chunk === null) return;

        // add the chunk as updated to the update task
        $this->plugin->getChunkUpdateTask()->addChunk($world, $chunk, $chunkX, $chunkZ);
    }

    public function onBlockBurn(BlockBurnEvent $e): void
    {
        $this->blockUpdate($e->getBlock()->getPosition());
    }

    public function onBlockForm(BlockFormEvent $e): void
    {
        $this->blockUpdate($e->getBlock()->getPosition());
    }

    public function onBlockGrow(BlockGrowEvent $e): void
    {
        $this->blockUpdate($e->getBlock()->getPosition());
    }

    public function onBlockMelt(BlockMeltEvent $e): void
    {
        $this->blockUpdate($e->getBlock()->getPosition());
    }

    public function onBlockPlace(BlockPlaceEvent $e): void
    {
        $this->blockUpdate($e->getBlockAgainst()->getPosition());
    }

    public function onBlockSpread(BlockSpreadEvent $e): void
    {
        $this->blockUpdate($e->getBlock()->getPosition());
    }

    public function onBlockTeleport(BlockTeleportEvent $e): void
    {
        $this->blockUpdate($e->getBlock()->getPosition());
    }

    public function onLeavesDecay(LeavesDecayEvent $e): void
    {
        $this->blockUpdate($e->getBlock()->getPosition());
    }

    public function onStructureGrow(StructureGrowEvent $e): void
    {
        $this->blockUpdate($e->getBlock()->getPosition());
    }
}