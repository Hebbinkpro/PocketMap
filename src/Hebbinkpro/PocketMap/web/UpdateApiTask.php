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

namespace Hebbinkpro\PocketMap\web;

use Exception;
use GdImage;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\settings\WebApiSettings;
use Himbeer\LibSkin\LibSkin;
use Himbeer\LibSkin\SkinConverter;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class UpdateApiTask extends Task
{
    public const HEAD_IMG_SIZE = 32;

    private PocketMap $plugin;
    private array $visibleWorlds;
    private string $apiFolder;
    private WebApiSettings $apiSettings;

    public function __construct(PocketMap $plugin)
    {
        $this->plugin = $plugin;
        $this->apiFolder = $this->plugin->getTmpApiFolder();
    }

    public function onRun(): void
    {
        $this->apiSettings = PocketMap::getSettingsManager()->getApi();

        $worlds = $this->apiSettings->getWorlds();
        if (sizeof($worlds) == 0) $worlds = $this->plugin->getWorldNames();
        $this->visibleWorlds = $worlds;

        $this->setWorldData();
        $this->setPlayerData();
    }

    private function setWorldData(): void
    {
        $worldData = [];
        foreach ($this->visibleWorlds as $name) {
            // get a loaded world
            $world = $this->plugin->getLoadedWorld($name);
            if ($world === null) continue;

            // set the world data
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

        // store the world data
        file_put_contents($this->apiFolder . "worlds.json", json_encode($worldData));
    }

    private function setPlayerData(): void
    {
        $onlinePlayers = $this->plugin->getServer()->getOnlinePlayers();

        $playerData = [];

        // get the config of the players
        if (!$this->apiSettings->playersVisible()) {
            file_put_contents($this->apiFolder . "players.json", []);
            return;
        }


        // loop through all players
        foreach ($onlinePlayers as $player) {
            $this->updatePlayerSkin($player);
            $world = $player->getWorld()->getFolderName();

            // the world is not visible, or the player is not visible
            if (!$this->isWorldVisible($world) ||
                in_array($world, $this->apiSettings->getPlayerHideWorlds(), true) ||
                in_array($player->getName(), $this->apiSettings->getHiddenPlayers(), true)) continue;

            // create an empty list for the world
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

            if ($this->apiSettings->showY()) $data["pos"]["y"] = $pos->getFloorY();

            $playerData[$world]["{$player->getUniqueId()}"] = $data;
        }

        file_put_contents($this->apiFolder . "players.json", json_encode($playerData));
    }

    private function updatePlayerSkin(Player $player): void
    {
        $skin = $player->getSkin();
        $skinFile = $this->apiFolder . "skin/{$skin->getSkinId()}.png";
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

    private function isWorldVisible(World|string $world): bool
    {
        if ($world instanceof World) $world = $world->getFolderName();
        return in_array($world, $this->visibleWorlds);
    }
}
