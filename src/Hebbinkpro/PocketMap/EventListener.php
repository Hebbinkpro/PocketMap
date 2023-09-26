<?php
/*
 *   _____           _        _   __  __
 *  |  __ \         | |      | | |  \/  |
 *  | |__) |__   ___| | _____| |_| \  / | __ _ _ __
 *  |  ___/ _ \ / __| |/ / _ \ __| |\/| |/ _` | '_ \
 *  | |  | (_) | (__|   <  __/ |_| |  | | (_| | |_) |
 *  |_|   \___/ \___|_|\_\___|\__|_|  |_|\__,_| .__/
 *                                            | |
 *                                            |_|
 *
 * Copyright (C) 2023 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap;

use Hebbinkpro\PocketMap\utils\ChunkUtils;
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
    private int $chunkCooldownTime;
    private array $chunkCooldown;

    public function __construct(PocketMap $plugin)
    {
        $this->plugin = $plugin;
        $this->chunkCooldown = [];
        $this->chunkCooldownTime = PocketMap::getConfigManger()->getInt("renderer.chunk-renderer.chunk-cooldown", 60);
    }

    public function onWorldLoad(WorldLoadEvent $e): void
    {
        // create a world renderer for the loaded world
        $renderer = $this->plugin->createWorldRenderer($e->getWorld());
        $this->plugin->getLogger()->debug("Created renderer for world: " . $e->getWorld()->getFolderName());

        if (!is_dir($renderer->getRenderPath())) {
            $this->plugin->getLogger()->warning("Could not create renderer for world: " . $e->getWorld()->getFolderName() . ". No directory found.");
            $this->plugin->removeWorldRenderer($e->getWorld());
            return;
        }

        // check if the render folder for the world is empty
        if (count(scandir($renderer->getRenderPath())) <= 2) {
            // the renders folder is empty
            // load the full world
            $this->plugin->getLogger()->notice("Starting full world render of world: " . $renderer->getWorld()->getFolderName());
            $this->plugin->getLogger()->warning("It is possible to notice a drop in server performance during a full world render.");
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
        $renderer = PocketMap::getWorldRenderer($world->getFolderName());
        if ($renderer === null) {
            $this->plugin->getLogger()->debug("Renderer of world: {$world->getFoldername()} not found!");
            return;
        }

        // chunk is not yet rendered
        if (!$renderer->isChunkRendered($cx, $cz)) {
            // it's a new chunk
            if ($e->isNewChunk()) {
                $chunk = $e->getChunk();
                $chunkData = ChunkUtils::getChunkData($world, $cx, $cz, $chunk);
                // save the chunk, otherwise the RegionChunksLoader cannot read the chunk data
                // this will cause the renderer to generate a black chunk in your beautiful map :(
                $world->getProvider()->saveChunk($cx, $cz, $chunkData, $chunk->getTerrainDirtyFlags());
            }

            $this->plugin->getLogger()->debug("Found a not rendered chunk: $cx,$cz in world: " . $world->getFolderName());
            $this->plugin->getChunkRenderer()->addChunk($renderer, $cx, $cz);
        }
    }

    public function onBlockBreak(BlockBreakEvent $e): void
    {
        $this->blockUpdate($e->getBlock()->getPosition());
    }

    private function blockUpdate(Position $pos): void
    {
        // just update the region cool-downs to make sure the list is up-to-date
        $this->updateChunkCooldown();

        $world = $pos->getWorld();
        $chunkX = floor($pos->getX() / 16);
        $chunkZ = floor($pos->getZ() / 16);

        // get the chunk
        $chunk = $world->getChunk($chunkX, $chunkZ);
        $renderer = PocketMap::getWorldRenderer($world->getFolderName());
        if ($chunk === null || $renderer === null) return;

        if ($this->hasChunkCooldown($chunkX, $chunkZ)) return;

        // add the chunk as updated to the update task
        $this->plugin->getChunkRenderer()->addChunk($renderer, $chunkX, $chunkZ);
        $this->setChunkCooldown($chunkX, $chunkZ);
    }

    /**
     * Check if the cool-downs still hold
     * @return void
     */
    private function updateChunkCooldown(): void
    {
        $onCooldown = [];

        // loop through all cool-downs and remove the one's that are expired
        foreach ($this->chunkCooldown as [$r, $time]) {
            if (time() - $time < $this->chunkCooldownTime) {
                $onCooldown[] = [$r, $time];
            }
        }

        $this->chunkCooldown = $onCooldown;
    }

    /**
     * Get if a region has a cool-down
     * @param int $chunkX x pos of the chunk
     * @param int $chunkZ z pos of the chunk
     * @return bool if the region has a cool down
     */
    public function hasChunkCooldown(int $chunkX, int $chunkZ): bool
    {
        foreach ($this->chunkCooldown as [$c, $time]) {
            if ($c === [$chunkX, $chunkZ]) return true;
        }

        return false;
    }

    /**
     * Give a chunk a cooldown
     * @param int $chunkX x pos of the chunk
     * @param int $chunkZ z pos of the chunk
     * @return void
     */
    public function setChunkCooldown(int $chunkX, int $chunkZ): void
    {
        $this->chunkCooldown[] = [[$chunkX, $chunkZ], time()];
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
        $this->blockUpdate($e->getPlayer()->getPosition());
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
