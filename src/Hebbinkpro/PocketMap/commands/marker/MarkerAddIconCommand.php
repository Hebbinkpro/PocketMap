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

use CortexPE\Commando\args\BlockPositionArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\PocketMap\marker\IconMarker;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;

class MarkerAddIconCommand extends BaseSubCommand
{

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array<mixed>|array{name: string, icon: string, pos?: Vector3, world?: string, id?: string} $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        if ($sender instanceof Player) {
            if (!isset($args["pos"])) $args["pos"] = $sender->getPosition();
            if (!isset($args["world"])) $args["world"] = $sender->getWorld()->getFolderName();
        } else if (sizeof($args) < 4) {
            $sender->sendMessage("§cInvalid amount of arguments given");
            return;
        }
        $world = $plugin->getLoadedWorld($args["world"]);
        if ($world === null) {
            $sender->sendMessage("§cInvalid world given");
            return;
        }

        $name = $args["name"];
        $icon = $args["icon"];
        $pos = Position::fromObject($args["pos"], $world);
        $id = $args["id"] ?? null;

        $markers = PocketMap::getMarkers();
        if (!$markers->isIcon($icon)) {
            $sender->sendMessage("§cInvalid icon");
            return;
        }


        $marker = new IconMarker($id, $name, $pos, $icon);
        if (!$markers->isMarker($pos->getWorld(), $marker->getId())) {
            $markers->addMarker($pos->getWorld(), $marker);
            $sender->sendMessage("[PocketMap] Marker '$name' is added to world '{$args["world"]}'");
        }
        else $sender->sendMessage("§cThe given marker ID is already in use");
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermissions(["pocketmap.cmd.marker.add"]);

        $this->registerArgument(0, new RawStringArgument("name"));
        $this->registerArgument(1, new MarkerIconArgument("icon", PocketMap::getMarkers()));
        $this->registerArgument(2, new BlockPositionArgument("pos", true));
        $this->registerArgument(3, new RawStringArgument("world", true));
        $this->registerArgument(4, new RawStringArgument("id", true));
    }
}