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
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\command\CommandSender;

class MarkerCommand extends BaseSubCommand
{

    protected function prepare(): void
    {

        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        $this->setPermissions(["pocketmap.cmd.marker"]);

        $this->registerSubCommand(new MarkerAddCommand($plugin, "add", "Add a marker"));
        $this->registerSubCommand(new MarkerRemoveCommand($plugin, "remove", "Remove a marker"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage($this->getUsageMessage());
    }
}