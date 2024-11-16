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

namespace Hebbinkpro\PocketMap\commands\marker;

use CortexPE\Commando\args\BlockPositionArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\PocketMap\marker\PolygonMarker;
use Hebbinkpro\PocketMap\PocketMap;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

class MarkerAddAreaCommand extends BaseSubCommand
{

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array<mixed>|array{name: string, pos1: Vector3, pos2?: Vector3, world?: string, id?: string} $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var PocketMap $plugin */
        $plugin = $this->getOwningPlugin();

        if ($sender instanceof Player) {
            if (!isset($args["pos2"])) $args["pos2"] = $sender->getPosition()->floor();
            if (!isset($args["world"])) $args["world"] = $sender->getWorld()->getFolderName();
        } else if (sizeof($args) < 4) {
            $sender->sendMessage("§cInvalid amount of arguments given");
            return;
        }
        $world = $plugin->getLoadedWorld($args["world"]);
        if ($world === null) {
            $sender->sendMessage("§cInvalid world given");
            return;
        }

        $name = $args["name"];
        $id = $args["id"] ?? null;

        $pos1 = $args["pos1"];
        $pos2 = $args["pos2"];


        if ($this->addMarker($name, $pos1, $pos2, $world, $id)) {
            $sender->sendMessage("[PocketMap] Marker '$name' is added to world '{$args["world"]}'");
        } else $sender->sendMessage("§cThe given marker ID is already in use");


    }

    protected function addMarker(string $name, Vector3 $pos1, Vector3 $pos2, World $world, ?string $id): bool
    {
        // create a rectangular polygon positions list
        $positions = [
            $pos1,
            new Vector3($pos1->getX(), 0, $pos2->getZ()),
            $pos2,
            new Vector3($pos2->getX(), 0, $pos1->getZ())
        ];

        $marker = new PolygonMarker($id, $name, $positions);

        $markers = PocketMap::getMarkers();
        if ($markers->isMarker($world, $marker->getId())) return false;

        $markers->addMarker($world, $marker);
        return true;
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermissions(["pocketmap.cmd.marker.add"]);

        $this->registerArgument(0, new RawStringArgument("name"));
        $this->registerArgument(1, new BlockPositionArgument("pos1"));
        $this->registerArgument(2, new BlockPositionArgument("pos2", true));
        $this->registerArgument(3, new RawStringArgument("world", true));
        $this->registerArgument(4, new RawStringArgument("id", true));
    }
}