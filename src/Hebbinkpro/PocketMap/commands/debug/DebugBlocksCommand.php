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
 * Copyright (c) 2024 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\commands\debug;

use CortexPE\Commando\args\BlockPositionArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;

class DebugBlocksCommand extends BaseSubCommand
{
    public const ROW_SIZE = 64;

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array<mixed>|array{pos?: Vector3, world?: string} $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (!isset($args["pos"])) $args["pos"] = $sender->getPosition();
            if (!isset($args["world"])) $args["world"] = $sender->getWorld()->getFolderName();
        } elseif (sizeof($args) < 2) {
            $sender->sendMessage("§cNo position given");
            return;
        }

        $startPos = $args["pos"];
        $world = Server::getInstance()->getWorldManager()->getWorldByName($args["world"]);

        if ($world === null) {
            $sender->sendMessage("§cWorld " . $args["world"] . " is not loaded");
            return;
        }

        $blocks = RuntimeBlockStateRegistry::getInstance()->getAllKnownStates();
        $blockIndexes = array_keys($blocks);
        $totalBlocks = sizeof($blocks);

        $colSize = $totalBlocks / self::ROW_SIZE;

        $sender->sendMessage("§a[WARNING] It is possible to encounter lag during the execution of this command");
        $sender->sendMessage("§ePlacing $totalBlocks blocks...");

        $blockIndex = 0;
        $rowSpaces = 2;
        $colSpaces = 2;

        $y = $startPos->getFloorY();
        for ($row = 0; $row <= self::ROW_SIZE; $row++) {
            $x = $row + ($row * $rowSpaces);
            for ($col = 0; $col <= $colSize; $col++) {
                if ($blockIndex >= $totalBlocks) break;

                $z = $col + ($col * $colSpaces);

                // get the current block
                $block = $blocks[$blockIndexes[$blockIndex] ?? 0] ?? null;

                if ($block !== null) {
                    // get the current position
                    $pos = $startPos->add($x, $y, $z);

                    // load the chunk the block should be placed in
                    [$cx, $cz] = [$pos->getFloorX() >> 4, $pos->getFloorZ() >> 4];
                    if (!$world->isChunkLoaded($cx, $cz)) $world->loadChunk($cx, $cz);

                    // place the block
                    $world->setBlock($pos, $block, false);
                }

                // update i to get the next block
                $blockIndex++;
            }
        }

        $sender->sendMessage("§aBlock placing completed");
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermission("pocketmap.cmd.debug.blocks");

        $this->registerArgument(0, new BlockPositionArgument("pos", true));
        $this->registerArgument(1, new RawStringArgument("world", true));
    }
}