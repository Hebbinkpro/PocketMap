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

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\command\CommandSender;
use pocketmine\utils\Filesystem;

class ReloadCommand extends BaseSubCommand
{

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermissions(["pocketmap.cmd.reload"]);

        $this->registerArgument(0, new RawStringArgument("part", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args["part"])) {
            $sender->sendMessage("--- PocketMap Reload ---");
            $sender->sendMessage("By reloading a part of the plugin you can apply some changes without restarting the server.");
            $sender->sendMessage("§eNotice: Not all changes can be applied without a restart.");
            $sender->sendMessage("- config => reload the config");
            $sender->sendMessage("- web => reload the web config");
            $sender->sendMessage("- data => reload the plugin data");
            return;
        }

        $system = $args["part"];

        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        switch ($system) {
            case "config":
                $plugin->loadConfig();
                $sender->sendMessage("[PocketMap] The config is reloaded");
                break;

            case "web":
                $plugin->loadWebConfig();
                $sender->sendMessage("[PocketMap] The web config is reloaded");
                break;

            case "data":
                $plugin->generateFolderStructure();
                $sender->sendMessage("[PocketMap] The plugin data is reloaded");
                break;

            default:
                $sender->sendMessage("§cInvalid part. Execute '/pmap reload' for a list of all available parts");
        }
    }
}