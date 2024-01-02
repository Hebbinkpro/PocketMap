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
use Hebbinkpro\PocketMap\commands\debug\DebugCommand;
use Hebbinkpro\PocketMap\commands\marker\MarkerCommand;
use Hebbinkpro\PocketMap\commands\reload\ReloadCommand;
use Hebbinkpro\PocketMap\commands\render\RenderCommand;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\command\CommandSender;

class PocketMapCommand extends BaseCommand
{

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array<mixed> $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage("[PocketMap] Execute '/pmap help' for a list of all available command");
    }

    protected function prepare(): void
    {
        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        $this->setPermission("pocketmap.cmd");
        $this->registerSubCommand(new HelpCommand($plugin, "help", "Get a list of all commands", ["h"]));
        $this->registerSubCommand(new RenderCommand($plugin, "render", "Render a region of the world map", ["r"]));
        $this->registerSubCommand(new ReloadCommand($plugin, "reload", "Reload some parts of the plugin", ["rl"]));
        $this->registerSubCommand(new MarkerCommand($plugin, "marker", "Manage map markers", ["m"]));

        // Only register the sub command if debug is enabled
        if (PocketMap::getConfigManger()->getBool("debug")) $this->registerSubCommand(new DebugCommand($plugin, "debug", "Execute debug commands"));
    }
}