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

namespace Hebbinkpro\PocketMap\commands\reload;

use CortexPE\Commando\BaseSubCommand;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\command\CommandSender;

class ReloadWeb extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {

        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();
        $plugin->loadWebConfig();
        $sender->sendMessage("[PocketMap] The web config is reloaded");
    }

    protected function prepare(): void
    {
        $this->setPermissions(["pocketmap.cmd.reload.web"]);
    }
}