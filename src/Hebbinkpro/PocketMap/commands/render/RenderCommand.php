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

namespace Hebbinkpro\PocketMap\commands\render;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\region\Region;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class RenderCommand extends BaseSubCommand
{
    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array{region?: string, x?: int, z?: int, world?: string, zoom?: int}|array<mixed> $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        if (!isset($args["region"])) {
            if ($sender instanceof Player) {
                $pos = $sender->getPosition()->divide(16)->floor();
                if (!isset($args["x"])) $args["x"] = $pos->getX();
                if (!isset($args["z"])) $args["z"] = $pos->getZ();
                if (!isset($args["world"])) $args["world"] = $sender->getWorld()->getFolderName();

            } elseif (sizeof($args) < 3) {
                $sender->sendMessage("§cNot all arguments are provided: " . $this->getUsageMessage());
                return;
            }

            if (!isset($args["zoom"])) $args["zoom"] = 0;

            $x = $args["x"];
            $z = $args["z"];
            $worldName = $args["world"];
            $zoom = $args["zoom"];

            if ($zoom < WorldRenderer::MIN_ZOOM || $zoom > WorldRenderer::MAX_ZOOM) {
                $sender->sendMessage("§cZoom not in range: [" . WorldRenderer::MIN_ZOOM . ", " . WorldRenderer::MAX_ZOOM . "]");
                return;
            }

            $world = $plugin->getLoadedWorld($worldName);
            if ($world === null) {
                $sender->sendMessage("§cWorld '$worldName' not found");
                return;
            }

            $renderer = PocketMap::getWorldRenderer($world);
            if ($renderer === null) {
                $sender->sendMessage("§cSomething went wrong");
                return;
            }

            $region = $renderer->getRegion($zoom, $x, $z, true);
        } else {
            /** @var string $rName */
            $rName = $args["region"];
            $region = Region::getByName($rName);

            if ($region === null) {
                $sender->sendMessage("§cInvalid region. Format: world/zoom/x,z");
                return;
            }

            $world = $plugin->getLoadedWorld($region->getWorldName());
            if ($world === null) {
                $sender->sendMessage("§cWorld '{$region->getWorldName()}' not found");
                return;
            }
            $renderer = PocketMap::getWorldRenderer($world);
        }

        if ($renderer === null) {
            $sender->sendMessage("§cSomething went wrong");
            return;
        }

        $res = $plugin->getChunkScheduler()->addChunksByRegion($renderer, $region);

        if ($res) $sender->sendMessage("[PocketMap] Rendering region: " . $region->getName() . " (" . pow($region->getTotalChunks(), 2) . " chunks)");
        else $sender->sendMessage("§cCannot render region " . $region->getName() . ", already scheduled.");

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
        $this->registerArgument(0, new RawStringArgument("region", true));
    }
}