<?php

namespace Hebbinkpro\PocketMap\commands\debug;

use CortexPE\Commando\args\BlockPositionArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\WorldManager;

class DebugBlocksCommand extends BaseSubCommand
{
    public const ROW_SIZE = 64;

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermission("pocketmap.cmd.debug.blocks");

        $this->registerArgument(0, new BlockPositionArgument("pos", true));
        $this->registerArgument(1, new RawStringArgument("world", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player && (!isset($args["pos"]) || !isset($args["world"]))) {
            $sender->sendMessage("§cNo position given");
            return;
        }

        $startPos = $args["pos"] ?? $sender->getPosition();
        $world = isset($args["world"]) ? Server::getInstance()->getWorldManager()->getWorldByName($args["world"]) : $sender->getWorld();

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
            $x = $row + ($row*$rowSpaces);
            for ($col = 0; $col <= $colSize; $col++) {
                if ($blockIndex >= $totalBlocks) break;

                $z = $col + ($col*$colSpaces);

                // get the current block
                $block = $blocks[$blockIndexes[$blockIndex] ?? 0] ?? null;

                if ($block !== null) {
                    // get the current position
                    $pos = $startPos->add($x, $y, $z);

                    // load the chunk the block should be placed in
                    [$cx,$cz] = [$pos->getFloorX() >> 4, $pos->getFloorZ() >> 4];
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
}