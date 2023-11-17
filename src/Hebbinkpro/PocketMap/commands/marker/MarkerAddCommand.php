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

use CortexPE\Commando\BaseSubCommand;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\command\CommandSender;

class MarkerAddCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage($this->getUsageMessage());
    }

    /**
     */
    protected function prepare(): void
    {
        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        $this->setPermissions(["pocketmap.cmd.marker.add"]);

        $this->registerSubCommand(new MarkerAddIconCommand($plugin, "icon", "Create an icon marker", ["i"]));
        $this->registerSubCommand(new MarkerAddCircleCommand($plugin, "circle", "Create an icon marker", ["c"]));
        $this->registerSubCommand(new MarkerAddAreaCommand($plugin, "area", "Create an icon marker", ["a"]));
        $this->registerSubCommand(new MarkerAddLineCommand($plugin, "line", "Create an icon marker", ["l"]));


    }
}