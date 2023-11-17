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

namespace Hebbinkpro\PocketMap\commands\render;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\scheduler\ChunkSchedulerTask;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class RenderCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var array{x: int, z: int, zoom: int, world: string} $args */

        if (sizeof($args) < 4) {
            if ($sender instanceof Player) {
                $pos = $sender->getPosition()->divide(16)->floor();
                if (!isset($args["x"])) $args["x"] = $pos->getX();
                if (!isset($args["z"])) $args["z"] = $pos->getZ();
                if (!isset($args["world"])) $args["world"] = $sender->getWorld()->getFolderName();
                if (!isset($args["zoom"])) $args["zoom"] = 0;

            } else {
                $sender->sendMessage("§cNot all arguments are provided: " . $this->getUsageMessage());
                return;
            }
        }

        $x = $args["x"];
        $z = $args["z"];
        $world = $args["world"];
        $zoom = $args["zoom"];

        if ($zoom < 0 || $zoom > 8) {
            $sender->sendMessage("§cZoom not in range: [0, 8]");
            return;
        }


        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        $renderer = PocketMap::getWorldRenderer($world);
        if ($renderer === null) {
            if (!$plugin->getServer()->getWorldManager()->loadWorld($world)) {
                $sender->sendMessage("§cWorld not found: $world");
                return;
            }
            $renderer = PocketMap::getWorldRenderer($world);
        }

        $region = $renderer->getRegion($zoom, $x, $z, true);
        if ($zoom == 0) $renderer->startRegionRender($region);
        else $plugin->getChunkScheduler()->addChunks($renderer, $region->getChunks(), ChunkSchedulerTask::CHUNK_GENERATOR_CURRENT);

        $sender->sendMessage("[PocketMap] Rendering region: " . $region->getName() . " (" . $region->getTotalChunks() . " chunks)");

    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        $this->setPermissions(["pocketmap.cmd.render"]);

        $this->registerSubCommand(new RenderFullCommand($plugin, "full", "Create a full world render"));
        $this->registerSubCommand(new RenderLookupCommand($plugin, "lookup", "Lookup the chunk and region coords of a given world coord"));

        $this->registerArgument(0, new IntegerArgument("x", true));
        $this->registerArgument(1, new IntegerArgument("z", true));
        $this->registerArgument(2, new RawStringArgument("world", true));
        $this->registerArgument(3, new IntegerArgument("zoom", true));
    }
}