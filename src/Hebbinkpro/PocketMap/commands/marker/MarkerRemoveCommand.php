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

namespace Hebbinkpro\PocketMap\commands\marker;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class MarkerRemoveCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        if (!isset($args["world"])) {
            if ($sender instanceof Player) $args["world"] = $sender->getWorld()->getFolderName();
            else {
                $sender->sendMessage("§cNo world given");
                return;
            }
        }
        $world = $plugin->getServer()->getWorldManager()->getWorldByName($args["world"]);
        $res = $plugin->getMarkers()->removeMarker($args["id"], $world);

        if ($res) $sender->sendMessage("[PocketMap] Marker " . $args["id"] . " is removed.");
        else $sender->sendMessage("§cMarker does not exist in world '{$args["world"]}'");

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