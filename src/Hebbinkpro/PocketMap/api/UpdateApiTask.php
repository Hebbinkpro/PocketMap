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

namespace Hebbinkpro\PocketMap\api;

use Exception;
use GdImage;
use Hebbinkpro\PocketMap\PocketMap;
use Himbeer\LibSkin\LibSkin;
use Himbeer\LibSkin\SkinConverter;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class UpdateApiTask extends Task
{
    public const HEAD_IMG_SIZE = 32;

    private PocketMap $plugin;
    private string $tmpFolder;

    public function __construct(PocketMap $plugin, string $tmpFolder)
    {
        $this->plugin = $plugin;
        $this->tmpFolder = $tmpFolder;
    }

    public function onRun(): void
    {
        $this->setWorldData();
        $this->setPlayerData();
    }

    private function setWorldData(): void
    {
        /** @var array<string> $worlds */
        $worlds = PocketMap::getConfigManger()->getArray("api.worlds");
        if (sizeof($worlds) == 0) {
            $files = scandir($this->plugin->getServer()->getDataPath() . "worlds");
            if ($files === false) return;
            $worlds = array_diff($files, [".", ".."]);
        }

        $wm = $this->plugin->getServer()->getWorldManager();

        $worldData = [];
        foreach ($worlds as $name) {
            if (!$this->isWorldVisible($name) || !$wm->loadWorld($name)) continue;

            $world = $this->plugin->getLoadedWorld($name);
            if ($world === null) continue;

            $data = $world->getProvider()->getWorldData();
            $spawn = $world->getSpawnLocation();

            $worldData[$name] = [
                "name" => $name,
                "spawn" => [
                    "x" => $spawn->getFloorX(),
                    "z" => $spawn->getFloorZ()
                ],
                "generator" => $data->getGenerator(),
                "time" => $world->getTime()
            ];
        }

        file_put_contents($this->tmpFolder . "worlds.json", json_encode($worldData));
    }

    private function isWorldVisible(World|string $world): bool
    {
        if ($world instanceof World) $world = $world->getFolderName();
        $list = PocketMap::getConfigManger()->getArray("api.worlds");
        return sizeof($list) == 0 || in_array($world, $list, true);
    }

    private function setPlayerData(): void
    {
        $onlinePlayers = $this->plugin->getServer()->getOnlinePlayers();

        $playerData = [];

        // get the config of the players
        $cfg = PocketMap::getConfigManger()->getManager("api.players");
        if ($cfg === null) return;

        // the players are visible eon the map
        if ($cfg->getBool("visible")) {
            // loop through all players
            foreach ($onlinePlayers as $player) {
                $this->updatePlayerSkin($player);
                $world = $player->getWorld()->getFolderName();

                // the world is not visible, or the player is not visible
                if (!$this->isWorldVisible($world) ||
                    in_array($world, $cfg->getArray("hide-worlds"), true) ||
                    in_array($player->getName(), $cfg->getArray("hide-players"), true)) continue;

                // create empty list for the world
                if (!isset($playerData[$world])) $playerData[$world] = [];


                $pos = $player->getPosition();
                $skin = $player->getSkin();
                // add the player to the list
                $data = [
                    "name" => $player->getName(),
                    "uuid" => $player->getUniqueId(),
                    "skin" => [
                        "id" => $skin->getSkinId(),
                        "size" => self::HEAD_IMG_SIZE
                    ],
                    "pos" => [
                        "x" => $pos->getFloorX(),
                        "z" => $pos->getFloorZ()
                    ]
                ];

                if ($cfg->getBool("show-y-coordinate")) $data["pos"]["y"] = $pos->getFloorY();

                $playerData[$world]["{$player->getUniqueId()}"] = $data;
            }
        }


        file_put_contents($this->tmpFolder . "players.json", json_encode($playerData));
    }

    private function updatePlayerSkin(Player $player): void
    {
        $skin = $player->getSkin();
        $skinFile = $this->tmpFolder . "skin/{$skin->getSkinId()}.png";
        if (file_exists($skinFile)) return;

        try {
            /** @var GdImage $skinImg */
            $skinImg = SkinConverter::skinDataToImage($skin->getSkinData());
        } catch (Exception $e) {
            $this->plugin->getLogger()->warning("Could not save skin '{$skin->getSkinId()}' from player '{$player->getName()}'");
            $this->plugin->getLogger()->error($e);
            return;
        }

        // get the size of the image
        $size = LibSkin::SKIN_WIDTH_MAP[strlen($skin->getSkinData())];
        // get the height/width of the head
        $headSize = $size / 8;

        // create the head img
        $headImg = imagecreatetruecolor(self::HEAD_IMG_SIZE, self::HEAD_IMG_SIZE);
        if ($headImg === false) return;
        // copy the head from the skin img to the head img
        imagecopyresampled($headImg, $skinImg, 0, 0, $headSize, $headSize, self::HEAD_IMG_SIZE, self::HEAD_IMG_SIZE, $headSize, $headSize);

        // save the head img
        imagepng($headImg, $skinFile);
    }
}
