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

namespace Hebbinkpro\PocketMap\commands\marker;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class MarkerRemoveCommand extends BaseSubCommand
{

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array{id: string, world?:string}|array<mixed> $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();


        if ($sender instanceof Player) {
            if (!isset($args["world"])) $args["world"] = $sender->getWorld()->getFolderName();
        } else if (!isset($args["world"])) {
            $sender->sendMessage("§cNo world given");
            return;
        }

        /** @var string $worldName */
        $worldName = $args["world"];
        $id = $args["id"];

        $world = $plugin->getLoadedWorld($worldName);

        if ($world === null) {
            $sender->sendMessage("§cWorld '$worldName' not found");
            return;
        }

        $markers = PocketMap::getMarkers();
        if (!$markers->isMarker($world, $id)) {
            if ($id === "all") {
                PocketMap::getMarkers()->removeMarkersFromWorld($world);
                $sender->sendMessage("[PocketMap] All markers from world '{$world->getFolderName()}' have been removed.");
                return;
            }

            $sender->sendMessage("§cNo marker with id '$id' found");
            return;
        }

        PocketMap::getMarkers()->removeMarker($world, $id);
        $sender->sendMessage("[PocketMap] Marker $id has been removed.");

    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermissions(["pocketmap.cmd.marker.remove"]);

        $this->registerArgument(0, new RawStringArgument("id"));
        $this->registerArgument(1, new RawStringArgument("world", true));
    }
}