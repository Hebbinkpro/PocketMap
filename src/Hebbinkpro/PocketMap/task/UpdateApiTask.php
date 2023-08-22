<?php

namespace Hebbinkpro\PocketMap\task;

use Exception;
use Hebbinkpro\PocketMap\PocketMap;
use Himbeer\LibSkin\LibSkin;
use Himbeer\LibSkin\SkinConverter;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class UpdateApiTask extends Task
{
    public const HEAD_IMG_SIZE = 32;

    private PluginBase $plugin;
    private string $tmpFolder;

    public function __construct(PluginBase $plugin, string $tmpFolder)
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
        $worlds = PocketMap::getConfigManger()->getArray("api.worlds");
        if (empty($worlds)) $worlds = array_diff(scandir($this->plugin->getServer()->getDataPath() . "worlds"), [".", ".."]);

        $wm = $this->plugin->getServer()->getWorldManager();

        $worldData = [];
        foreach ($worlds as $name) {
            if (!$this->isWorldVisible($name) || !$wm->loadWorld($name)) continue;

            $world = $wm->getWorldByName($name);
            $data = $world->getProvider()->getWorldData();
            $worldData[] = [
                "name" => $name,
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
        return empty($list) || in_array($world, $list);
    }

    private function setPlayerData(): void
    {
        $onlinePlayers = $this->plugin->getServer()->getOnlinePlayers();

        $playerData = [];

        // get the config of the players
        $cfg = PocketMap::getConfigManger()->getManager("api.players");

        // the players are visible eon the map
        if ($cfg->getBool("visible")) {
            // loop through all players
            foreach ($onlinePlayers as $player) {
                $this->updatePlayerSkin($player);
                $world = $player->getWorld()->getFolderName();

                // the world is not visible, or the player is not visible
                if (!$this->isWorldVisible($world) ||
                    in_array($world, $cfg->getArray("hide-worlds")) ||
                    in_array($player->getName(), $cfg->getArray("hide-players"))) continue;

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
        // copy the head from the skin img to the head img
        imagecopyresampled($headImg, $skinImg, 0, 0, $headSize, $headSize, self::HEAD_IMG_SIZE, self::HEAD_IMG_SIZE, $headSize, $headSize);

        // save the head img
        imagepng($headImg, $skinFile);
    }
}
