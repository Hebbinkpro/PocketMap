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

namespace Hebbinkpro\PocketMap;

use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use Exception;
use Hebbinkpro\PocketMap\api\MarkerManager;
use Hebbinkpro\PocketMap\api\UpdateApiTask;
use Hebbinkpro\PocketMap\commands\PocketMapCommand;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\scheduler\ChunkSchedulerTask;
use Hebbinkpro\PocketMap\scheduler\RenderSchedulerTask;
use Hebbinkpro\PocketMap\textures\TerrainTextures;
use Hebbinkpro\PocketMap\textures\TerrainTexturesOptions;
use Hebbinkpro\PocketMap\utils\ConfigManager;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use Hebbinkpro\WebServer\exception\WebServerException;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Hebbinkpro\WebServer\route\Router;
use Hebbinkpro\WebServer\WebServer;
use JsonException;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\event\Listener;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use pocketmine\world\World;

class PocketMap extends PluginBase implements Listener
{
    public const CONFIG_VERSION = 1.7;
    public const WEB_VERSION = 1.2;

    public const RESOURCE_PACK_NAME = "v1.20.50";
    public const TEXTURE_SIZE = 16;

    private static PocketMap $instance;
    /** @var WorldRenderer[] */
    private static array $worldRenderers = [];
    private ConfigManager $configManager;
    private TerrainTextures $terrainTextures;
    private WebServer $webServer;
    private RenderSchedulerTask $renderScheduler;
    private ChunkSchedulerTask $chunkScheduler;
    private MarkerManager $markers;

    public static function getFolder(): string
    {
        return self::$instance->getDataFolder();
    }

    /**
     * Get a world renderer by its world or the name of the world
     * @param World|string $world The world or the name of the world
     * @return WorldRenderer|null the WorldRenderer or null when it wasn't found
     */
    public static function getWorldRenderer(World|string $world): ?WorldRenderer
    {
        if (is_string($world)) $worldName = $world;
        else $worldName = $world->getFolderName();

        return self::$worldRenderers[$worldName] ?? null;
    }

    /**
     * @return MarkerManager
     */
    public static function getMarkers(): MarkerManager
    {
        return self::$instance->markers;
    }

    /**
     * Get a world by its name. If the world is not loaded, it will be loaded.
     * @param string $name
     * @return World|null the loaded world or null when it does not exist
     */
    public function getLoadedWorld(string $name): ?World
    {
        $wm = $this->getServer()->getWorldManager();
        if (!$wm->isWorldLoaded($name) && !$wm->loadWorld($name)) return null;

        return $wm->getWorldByName($name);
    }

    /**
     * Create a world renderer for a given world
     * @param World $world
     * @return WorldRenderer
     */
    public function createWorldRenderer(World $world): WorldRenderer
    {
        $path = $this->getDataFolder() . "renders/" . $world->getFolderName() . "/";
        $renderer = new WorldRenderer($world, $this->getTerrainTextures(), $path, $this->getRenderScheduler(), $this->chunkScheduler);
        self::$worldRenderers[$world->getFolderName()] = $renderer;
        return $renderer;
    }

    /**
     * Get the terrain textures
     * @return TerrainTextures
     */
    public function getTerrainTextures(): TerrainTextures
    {
        return $this->terrainTextures;
    }

    /**
     * Get the render scheduler
     * @return RenderSchedulerTask
     */
    public function getRenderScheduler(): RenderSchedulerTask
    {
        return $this->renderScheduler;
    }

    /**
     * Get the chunk scheduler
     * @return ChunkSchedulerTask
     */
    public function getChunkScheduler(): ChunkSchedulerTask
    {
        return $this->chunkScheduler;
    }

    /**
     * Remove a world renderer
     * @param World $world
     * @return void
     */
    public function removeWorldRenderer(World $world): void
    {
        unset(self::$worldRenderers[$world->getFolderName()]);
    }

    /**
     * @throws HookAlreadyRegistered
     */
    protected function onEnable(): void
    {
        self::$instance = $this;

        // load all resources
        $this->loadConfig();
        $this->generateFolderStructure();

        // load the terrain textures
        $this->loadTerrainTextures();

        // start all tasks
        $this->startTasks();

        $this->markers = new MarkerManager($this->getDataFolder() . "markers/");

        // register the event listener
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        if (!PacketHooker::isRegistered()) PacketHooker::register($this);

        $this->getServer()->getCommandMap()->register("pocketmap", new PocketMapCommand($this, "pocketmap", "PocketMap command", ["pmap"]));

        $this->loadAllWorlds();

        WebServer::register($this);

        try {
            // create the web server
            $this->createWebServer();
        } catch (Exception $e) {
            $this->disable("Could not start the web server.", $e->getMessage());
            return;
        }

        // if debug is enabled, check all block textures
        if ($this->configManager->getBool("debug")) {
            // list with not found type ids
            // this is used so that not all blocks with the same type id will be logged which causes unnecessary spam
            $notFound = [];
            foreach (RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $block) {
                $texture = $this->terrainTextures->getTextureByBlock($block);

                $id = $block->getTypeId();
                if ($texture === null && !in_array($id, $notFound, true)) {
                    $notFound[] = $id;
                    $textureName = TextureUtils::getBlockTextureName($block);
                    $this->getLogger()->warning("Cannot find texture of block: " . $block->getName() . ", ID: " . $block->getTypeId() . ", Texture: " . $textureName);

                }
            }
        }
    }

    /**
     * Load the config
     * @return void
     */
    public function loadConfig(): void
    {
        $folder = $this->getDataFolder();

        // save the config file
        $this->saveDefaultConfig();

        $config = $this->getConfig();

        $version = $config->get("version", -1.0);
        if (!is_float($version)) $version = -1.0;

        if ($version != self::CONFIG_VERSION) {
            $this->getLogger()->warning("The current version of PocketMap is using another config version. Your current config will be replaced. Expected: v" . self::CONFIG_VERSION . ", Found: v$version");
            $this->getLogger()->warning("You can find your old config in 'backup/config_v$version.yml'");

            if (!is_dir($folder . "backup")) mkdir($folder . "backup");

            // clone all contents from config.yml inside the backup config
            file_put_contents($folder . "backup/config_v$version.yml",
                file_get_contents($folder . "config.yml"));

            // save the new config
            $this->saveResource("config.yml", true);
            // update the config to use it in the config manager
            // don't use $this->getConfig(), because that will result in the OLD config
            $config = new Config($folder . "config.yml");
        }

        // construct the config manager
        $this->configManager = ConfigManager::fromConfig($config);
    }

    public function generateFolderStructure(): void
    {
        $folder = $this->getDataFolder();
        $file = $this->getFile() . "resources/";

        if (!is_dir($folder . "resource_packs")) {
            mkdir($folder . "resource_packs");
        }

        // load the resource pack files
        if (!is_dir($folder . "resource_packs/" . self::RESOURCE_PACK_NAME)) {
            Filesystem::recursiveCopy($file . "resource_packs", $folder . "resource_packs");
        }

        // create the renders folder
        if (!is_dir($folder . "renders")) {
            mkdir($folder . "renders");
        }

        // create the markers folder
        if (!is_dir($folder . "markers")) {
            Filesystem::recursiveCopy($file . "markers", $folder . "markers");
        }

        if (!is_dir($folder . "tmp")) {
            mkdir($folder . "tmp");
        }

        // create the regions folder inside tmp
        if (!is_dir($folder . "tmp/regions")) {
            mkdir($folder . "tmp/regions");
        }

        // create render folders for each world
        $worldFolders = scandir($this->getServer()->getDataPath() . "worlds/");
        if ($worldFolders === false) $worldFolders = [];

        foreach ($worldFolders as $worldName) {
            // it's not a world
            if (!is_dir($this->getServer()->getDataPath() . "worlds/$worldName")) continue;

            $contents = scandir($this->getServer()->getDataPath() . "worlds/$worldName");
            if ($contents === false || !in_array("level.dat", $contents, true)) continue;

            if (!is_dir($folder . "renders/$worldName")) {
                mkdir($folder . "renders/$worldName");
            }
        }

        if (!is_dir($folder . "tmp/api")) {
            mkdir($folder . "tmp/api");
        }

        if (!is_dir($folder . "tmp/api/skin")) {
            mkdir($folder . "tmp/api/skin");
        }
    }

    private function loadTerrainTextures(): void
    {
        $textureSettings = $this->configManager->getManager("textures");
        if ($textureSettings === null) {
            $this->getLogger()->error("Texture settings not found");
            return;
        }

        // get the fallback block
        $fallbackBlockId = $textureSettings->getString("fallback-block", "minecraft:bedrock");
        $fallbackBlock = StringToItemParser::getInstance()->parse($fallbackBlockId)?->getBlock() ?? null;

        // get the height overlay data
        $heightColor = $textureSettings->getInt("height-overlay.color", 0x000000);
        $heightAlpha = $textureSettings->getInt("height-overlay.alpha", 3);

        $options = new TerrainTexturesOptions($fallbackBlock, $heightColor, $heightAlpha);

        $path = $this->getDataFolder() . "resource_packs/";

        $terrainTextures = TerrainTextures::generate($this, $path, $options);
        if ($terrainTextures === null) {
            $this->disable("Cannot generate the terrain textures");
            return;
        }

        $this->terrainTextures = $terrainTextures;
    }

    /**
     * Disable the plugin, this should ONLY be used when the plugin is unusable to protect the plugins (maybe and servers) integrity
     * @param string ...$reason the reason why the plugin is disabled
     * @return void
     */
    private function disable(string...$reason): void
    {
        $this->getLogger()->emergency("Disabling the plugin");

        foreach ($reason as $r) {
            $this->getLogger()->emergency($r);
        }

        $this->getServer()->getPluginManager()->disablePlugin($this);
    }

    private function startTasks(): void
    {
        // start the render scheduler
        $this->renderScheduler = new RenderSchedulerTask($this);
        $this->getScheduler()->scheduleRepeatingTask($this->renderScheduler, $this->configManager->getInt("renderer.scheduler.run-period", 5));

        // start the chunk update task, this check every period if regions have to be updated
        $this->chunkScheduler = new ChunkSchedulerTask($this);
        $this->getScheduler()->scheduleRepeatingTask($this->chunkScheduler, $this->configManager->getInt("renderer.chunk-scheduler.run-period", 10));

        // start the api update task
        $updateApiTask = new UpdateApiTask($this, $this->getDataFolder() . "tmp/api/");
        $this->getScheduler()->scheduleRepeatingTask($updateApiTask, $this->configManager->getInt("api.update-period", 20));

    }

    /**
     * Load all worlds
     * @return void
     */
    private function loadAllWorlds(): void
    {
        $path = $this->getServer()->getDataPath() . "worlds/";
        $folders = scandir($path);
        if ($folders === false) $folders = [];

        foreach ($folders as $world) {
            if (!is_dir($path . $world) || in_array($world, [".", ".."], true) || !is_file($path . $world . "/level.dat")) continue;
            $this->getServer()->getWorldManager()->loadWorld($world);
        }
    }

    /**
     * Create the web server and set the routes
     * @throws PhpVersionNotSupportedException
     * @throws WebServerException
     */
    private function createWebServer(): void
    {
        // load the required files
        $this->loadWebFiles();
        $this->loadWebConfig();

        $webSettings = $this->configManager->getManager("web-server", true, ["address" => "127.0.0.1", "port" => 3000]);
        if ($webSettings === null) {
            $this->disable("Web server settings not found");
            return;
        }

        // create the web server
        $this->webServer = new WebServer($webSettings->getString("address", "127.0.0.1"), $webSettings->getInt("port", 3000));
        $router = $this->webServer->getRouter();

        $webFolder = $this->getDataFolder() . "web/";

        // main route
        $router->getFile("/", $webFolder . "pages/index.html");

        // all static files used by web pages
        $router->getStatic("/static", $webFolder . "static");

        // register the api router
        $router->route("/api/pocketmap", $this->registerApiRoutes());

        // start the web server
        $this->webServer->start();
    }

    private function loadWebFiles(): void
    {
        $folder = $this->getDataFolder() . "web/";

        // does not yet exist
        if (is_dir($folder)) {
            // config file does not exist
            $webConfig = new Config($folder . "config.json");

            $version = $webConfig->get("version", -1);
            if (!is_float($version)) $version = -1;

            // the correct web version is already loaded
            if ($version == self::WEB_VERSION) return;

            $this->getLogger()->warning("The current version of PocketMap is using another web version. The web files will be replaced. Expected: " . self::WEB_VERSION . ", Found: v$version");
            $this->getLogger()->warning("You can find your old web files in 'backup/web_v$version/'");

            if (!is_dir($this->getDataFolder() . "backup")) mkdir($this->getDataFolder() . "backup");

            // place files in the backup folder
            $backupFolder = $this->getDataFolder() . "backup/web-v$version";
            if (!is_dir($backupFolder)) {
                mkdir($this->getDataFolder() . "backup/web-v$version");
                Filesystem::recursiveCopy($folder, $backupFolder);
            }

            // remove the current files and add the new ones
            Filesystem::recursiveUnlink($folder);
        }

        // create the web folder and copy the contents
        mkdir($folder);
        Filesystem::recursiveCopy($this->getFile() . "resources/web", $folder);
    }

    public function loadWebConfig(): void
    {
        $webConfig = new Config($this->getDataFolder() . "web/config.json");
        $webManager = ConfigManager::fromConfig($webConfig);

        $validWorlds = self::getConfigManger()->getArray("api.worlds");
        $worldSettings = $webManager->getArray("worlds", []);

        foreach (self::$worldRenderers as $worldName => $renderer) {
            // we aren't allowed to load this world
            if (count($validWorlds) > 0 && !in_array($worldName, $validWorlds, true)) {
                // remove from the world settings list
                if (array_key_exists($worldName, $worldSettings)) unset($worldSettings[$worldName]);
                continue;
            }

            // already loaded
            if (array_key_exists($worldName, $worldSettings)) continue;

            $spawnPos = $renderer->getWorld()->getSpawnLocation();

            $worldSettings[$worldName] = [
                "zoom" => [
                    "min" => WorldRenderer::MIN_ZOOM,
                    "max" => WorldRenderer::MAX_ZOOM
                ],
                "view" => [
                    "x" => $spawnPos->getFloorX(),
                    "z" => $spawnPos->getFloorZ(),
                    "zoom" => (int)floor(WorldRenderer::MAX_ZOOM / 2)
                ]
            ];
        }

        $defaultWorld = $webManager->getString("default-world", "world");

        // default world does not exist (anymore)
        if (!array_key_exists($defaultWorld, $worldSettings)) {
            // the default world of the server isn't allowed to be displayed, use the first world in the list
            $defaultWorld = array_keys($worldSettings)[0] ?? null;
        }
        try {
            $webManager->setValue("default-world", $defaultWorld);
            $webManager->setValue("worlds", $worldSettings);
            $webManager->save();
        } catch (JsonException $e) {
            $this->disable("Cannot update the web config.", $e->getMessage());
        }
    }

    public static function getConfigManger(): ConfigManager
    {
        return self::$instance->configManager;
    }

    /**
     * Register all API routes to the web server
     * @throws PhpVersionNotSupportedException
     * @throws WebServerException
     */
    private function registerApiRoutes(): Router
    {
        $webFolder = $this->getDataFolder() . "web/";
        $apiFolder = $this->getDataFolder() . "tmp/api/";

        $router = new Router();

        $router->getFile("/config", $webFolder . "config.json");

        // get the world data
        $router->getFile("/worlds", $apiFolder . "worlds.json", "[]");

        // get player data
        $router->getFile("/players", $apiFolder . "players.json", "[]");

        // get the player heads
        $router->getStatic("/players/skin", $apiFolder . "skin");

        // get image renders
        $router->getStatic("/render", $this->getDataFolder() . "renders");

        // get markers
        $router->getFile("/markers", $this->getDataFolder() . "markers/markers.json", "[]");
        // get marker icons
        $router->getFile("/markers/icons", $this->getDataFolder() . "markers/icons.json", "[]");
        $router->getStatic("/markers/icons", $this->getDataFolder() . "markers/icons");

        return $router;
    }

    protected function onDisable(): void
    {
        // close the socket
        if ($this->webServer->isStarted()) $this->webServer->close();
    }
}