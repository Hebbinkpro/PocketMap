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

namespace Hebbinkpro\PocketMap\commands;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;

class PocketMapCommand extends BaseCommand
{

    protected function prepare(): void
    {
        $this->setPermissions(["pocketmap.cmd"]);
        $this->registerSubCommand(new HelpCommand("help", "Get a list of all commands"));
        $this->registerSubCommand(new RenderCommand("render", "Render a region of the world map"));
        $this->registerSubCommand(new ReloadCommand("reload", "Reload some parts of the plugin"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage("[PocketMap] Execute '/pmap help' for a list of all available command");
    }
}