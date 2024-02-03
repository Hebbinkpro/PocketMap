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

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class DebugCommand extends BaseSubCommand
{

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array<mixed> $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage("§e[WARNING] Executing debug commands can cause temporary lag on your server!");
    }

    protected function prepare(): void
    {
        $this->setPermission("pocketmap.cmd.debug");
        /** @var PluginBase $plugin */
        $plugin = $this->getOwningPlugin();

        $this->registerSubCommand(new DebugBlocksCommand($plugin, "blocks", "Loads a grid with all the registered blocks on your position"));
    }
}