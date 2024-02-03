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

namespace Hebbinkpro\PocketMap\commands\reload;

use CortexPE\Commando\BaseSubCommand;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\command\CommandSender;

class ReloadCommand extends BaseSubCommand
{
    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array<mixed> $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage("--- PocketMap Reload ---");
        $sender->sendMessage($this->getUsageMessage());
    }

    protected function prepare(): void
    {

        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        $this->setPermissions(["pocketmap.cmd.reload"]);

        $this->registerSubCommand(new ReloadConfig($plugin, "config", "Reload the config"));
        $this->registerSubCommand(new ReloadWeb($plugin, "web", "Reload the web config"));
        $this->registerSubCommand(new ReloadData($plugin, "data", "Reload the plugin data"));
    }
}