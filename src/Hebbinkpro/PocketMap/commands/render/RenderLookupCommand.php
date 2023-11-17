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

namespace Hebbinkpro\PocketMap\commands\render;

use CortexPE\Commando\args\BlockPositionArgument;
use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\PocketMap\region\BaseRegion;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class RenderLookupCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args["zoom"]) || $args["zoom"] < 0) $args["zoom"] = 0;

        if ($sender instanceof Player) {
            $pos = $sender->getPosition();
            if (!isset($args["pos"])) $args["pos"] = $pos;
        } elseif (count($args) < 1) {
            $this->sendError(BaseCommand::ERR_INSUFFICIENT_ARGUMENTS);
            return;
        }

        $pos = $args["pos"]->floor();
        $chunk = $pos->divide(16)->floor();
        $region = new BaseRegion(0, $chunk->getX(), $chunk->getZ());

        $sender->sendMessage("--- Render Lookup ---");
        $sender->sendMessage("Coords (x,z): " . $pos->getX() . "," . $pos->getZ());
        $sender->sendMessage("Chunk (x,z): " . $chunk->getX() . "," . $chunk->getZ());

        $sender->sendMessage("Regions (zoom/x,z):");
        for ($z = 0; $z <= $args["zoom"]; $z++) {
            $sender->sendMessage("- " . $region->getName());

            $region = $region->getNextZoomRegion();
            if ($region == null) break;
        }
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermissions(["pocketmap.cmd.render.lookup"]);

        $this->registerArgument(0, new BlockPositionArgument("pos", true));
        $this->registerArgument(1, new IntegerArgument("zoom", true));
    }
}