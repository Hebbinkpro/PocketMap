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
use Hebbinkpro\PocketMap\commands\PocketMapCommand;
use Hebbinkpro\PocketMap\extension\DebugWorldMarkers;
use Hebbinkpro\PocketMap\extension\ExtensionManager;
use Hebbinkpro\PocketMap\marker\MarkerManager;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\scheduler\ChunkSchedulerTask;
use Hebbinkpro\PocketMap\scheduler\RenderSchedulerTask;
use Hebbinkpro\PocketMap\settings\SettingsManager;
use Hebbinkpro\PocketMap\textures\TerrainTextures;
use Hebbinkpro\PocketMap\textures\TerrainTexturesOptions;
use Hebbinkpro\PocketMap\utils\ResourcePackUtils;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use Hebbinkpro\PocketMap\web\MapConfig;
use Hebbinkpro\PocketMap\web\UpdateApiTask;
use Hebbinkpro\PocketMap\web\WebServerManager;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\ServerProperties;
use pocketmine\utils\Filesystem;
use pocketmine\world\World;
use ZipArchive;

class PocketMap extends PluginBase implements Listener
{
    public const CONFIG_VERSION = 1.8;
    public const WEB_VERSION = 1.3;

    public const RESOURCE_PACK_NAME = "v1.21.40";
    public const TEXTURE_SIZE = 16;

    public const IGNORED_TEXTURES = [
        "piston_arm_collision",
        "sticky_piston_arm_collision",
        "moving_block",
        "unknown"
    ];

    private static PocketMap $instance;
    /** @var WorldRenderer[] */
    private static array $worldRenderers = [];
    private TerrainTextures $terrainTextures;
    private WebServerManager $webServer;
    private RenderSchedulerTask $renderScheduler;
    private ChunkSchedulerTask $chunkScheduler;
    private MarkerManager $markers;

    private SettingsManager $settingsManager;

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
     * Get the settings manager containing all plugin settings
     * @return SettingsManager
     */
    public static function getSettingsManager(): SettingsManager
    {
        return self::$instance->settingsManager;
    }

    /**
     * Get a world by its name. If the world is not loaded, it will be loaded (if load-worlds=true in the config).
     * @param string $name the world name
     * @return World|null the loaded world or null when it does not exist
     */
    public function getLoadedWorld(string $name): ?World
    {
        // get if loading worlds is allowed
        $loadWorlds = $this->settingsManager->getPocketMap()->loadWorlds();
        $wm = $this->getServer()->getWorldManager();

        // get if the world is loaded, otherwise load the world if it is allowed
        if (!$wm->isWorldLoaded($name) && (!$loadWorlds || !$wm->loadWorld($name))) {
            return null;
        }

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
     * Remove a world renderer
     * @param World $world
     * @return void
     */
    public function removeWorldRenderer(World $world): void
    {
        unset(self::$worldRenderers[$world->getFolderName()]);
    }

    protected function onLoad(): void
    {
        $extensions = ExtensionManager::getInstance();
        $extensions->registerExtension($this, "DebugWorldMarkers", DebugWorldMarkers::class);
    }

    /**
     * @throws HookAlreadyRegistered
     */
    protected function onEnable(): void
    {
        // create instances
        self::$instance = $this;
        $this->settingsManager = new SettingsManager();

        // load all resources
        $this->loadConfig();
        $this->generateFolderStructure();

        // load the markers
        $this->markers = new MarkerManager($this);
        $this->markers->load();

        // load the web files
        $this->loadWebFiles();
        $this->loadWebConfig();

        // load the terrain textures
        $this->loadTerrainTextures();

        $this->registerLibraries();

        // register things
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("pocketmap", new PocketMapCommand($this, "pocketmap", "PocketMap command", ["pmap"]));

        // start all tasks
        $this->startTasks();

        // create webserver manager
        $this->webServer = new WebServerManager($this, $this->settingsManager->getWebServer());
        // start the webserver
        $this->startWebServer();

        // if debug is enabled, run the debug function
        if ($this->settingsManager->getPocketMap()->debugEnabled()) {
            $this->enableDebug();
        }

        // enable the extensions
        ExtensionManager::getInstance()->enableAll();
    }

    /**
     * Load the config
     * @return void
     */
    public function loadConfig(): void
    {
        $folder = $this->getDataFolder();
        $config = $this->getConfig();

        $version = $config->get("version", -1.0);
        if (!is_float($version)) $version = -1.0;

        if ($version != self::CONFIG_VERSION) {
            // TODO improved config updater
            $this->getLogger()->warning("The current version of PocketMap is using another config version. Your current config will be replaced. Expected: v" . self::CONFIG_VERSION . ", Found: v$version");
            $this->getLogger()->warning("You can find your old config in 'backup/config_v$version.yml'");

            if (!is_dir($folder . "backup")) mkdir($folder . "backup");

            // clone all contents from config.yml inside the backup config
            file_put_contents($folder . "backup/config_v$version.yml",
                file_get_contents($folder . "config.yml"));

            // save the new config
            $this->saveResource("config.yml", true);
            $this->reloadConfig();
        }

        $this->settingsManager->load($this->getConfig());
    }

    /**
     * Create all required plugin data folders
     * @return void
     */
    private function generateFolderStructure(): void
    {
        $file = $this->getFile() . "resources/";

        // get all used folders
        $resourcePacksFolder = $this->getResourcePacksFolder();
        $rendersFolder = $this->getRendersFolder();
        $markersFolder = $this->getMarkersFolder();
        $tmpFolder = $this->getTmpFolder();
        $tmpApiFolder = $this->getTmpApiFolder();
        $tmpRegionsFolder = $this->getTmpRegionsFolder();

        if (!is_dir($resourcePacksFolder)) {
            mkdir($resourcePacksFolder);
        }

        // load the resource pack files
        if (!is_dir($resourcePacksFolder . self::RESOURCE_PACK_NAME)) {
            // extract the zip to the plugin data
            $src = $file . "resource_packs/" . self::RESOURCE_PACK_NAME . ".zip";
            $dest = $resourcePacksFolder . self::RESOURCE_PACK_NAME . ".zip";
            copy($src, $dest);

            // extract the zipped vanilla resource pack
            $pack = new ZippedResourcePack($dest);
            ResourcePackUtils::extractResourcePack($resourcePacksFolder, $pack, null, self::RESOURCE_PACK_NAME);

            // remove the zip
            unlink($dest);
        }

        // create the renders folder
        if (!is_dir($rendersFolder)) {
            mkdir($rendersFolder);
        }

        // create the markers folder
        if (!is_dir($markersFolder)) {
            Filesystem::recursiveCopy($file . "markers", $markersFolder);
        }

        if (!is_dir($markersFolder . "icons")) {
            mkdir($markersFolder . "icons");
        }

        // icons JSON does not exist extract the zip
        if (!is_file($markersFolder . "icons/icons.json")) {

            // copy the icons zip
            $src = $markersFolder . "icons.zip";
            copy($file . "markers/icons.zip", $src);

            // open the icons.zip and extract all the icons
            $zip = new ZipArchive();
            $zip->open($src);
            $zip->extractTo($markersFolder . "icons");
            $zip->close();

            // remove the zip
            unlink($src);
        }

        if (!is_dir($tmpFolder)) {
            mkdir($tmpFolder);
        }

        // create the regions folder inside tmp
        if (!is_dir($tmpRegionsFolder)) {
            mkdir($tmpRegionsFolder);
        }

        $visibleWorlds = $this->settingsManager->getApi()->getWorlds();
        $hiddenWorlds = sizeof($visibleWorlds) > 0;
        // create render folders for each world
        foreach ($this->getWorldNames() as $worldName) {
            // the world is hidden
            if ($hiddenWorlds && !in_array($worldName, $visibleWorlds)) continue;

            // create render folder for the world
            if (!is_dir($rendersFolder . $worldName)) {
                mkdir($rendersFolder . $worldName);
            }
        }

        // create api folder
        if (!is_dir($tmpApiFolder)) {
            mkdir($tmpApiFolder);
        }

        // create skin api folder
        if (!is_dir($tmpApiFolder . "skin")) {
            mkdir($tmpApiFolder . "skin");
        }
    }

    public function getResourcePacksFolder(): string
    {
        return $this->getDataFolder() . "resource_packs/";
    }

    public function getRendersFolder(): string
    {
        return $this->getDataFolder() . "renders/";
    }

    public function getMarkersFolder(): string
    {
        return $this->getDataFolder() . "markers/";
    }

    public function getTmpFolder(): string
    {
        return $this->getDataFolder() . "tmp/";
    }

    public function getTmpApiFolder(): string
    {
        return $this->getTmpFolder() . "api/";
    }

    public function getTmpRegionsFolder(): string
    {
        return $this->getTmpFolder() . "regions/";
    }

    /**
     * Get all world names
     * @return string[] All folder names of valid worlds in the worlds folder
     */
    public function getWorldNames(): array
    {
        $worldsFolder = $this->getServer()->getDataPath() . "worlds/";
        $worlds = [];
        foreach (scandir($worldsFolder) as $world) {
            $worldFolder = $worldsFolder . $world . "/";
            if (is_dir($worldFolder) && is_file($worldFolder . "level.dat")) {
                $worlds[] = $world;
            }
        }

        return $worlds;
    }

    /**
     * Load all web files
     * @return void
     */
    private function loadWebFiles(): void
    {
        $folder = $this->getWebFolder();

        // web files exist
        if (is_dir($folder)) {
            // check if a version file exists
            if (is_file($folder . "version.json")) {
                // get the version from the version file
                $versionContents = json_decode(file_get_contents($folder . "version.json"), true);
                $version = floatval($versionContents["version"] ?? 0);

                // validate the version
                if ($version == self::WEB_VERSION) return;

                $this->getLogger()->warning("The current version of PocketMap is using another version of the web files. The web files will be replaced. Expected: v" . self::WEB_VERSION . ", Found: v$version");
                $this->getLogger()->warning("You can find your old web files in 'backup/web_v$version/'");

                // backup the files
                $backupFolder = $this->getBackupFolder();
                if (!is_dir($backupFolder)) mkdir($backupFolder);

                // place files in the backup folder
                $backupFolder = $backupFolder . "web-v$version";
                if (!is_dir($backupFolder)) {
                    mkdir($backupFolder . "web-v$version");
                    Filesystem::recursiveCopy($folder, $backupFolder);
                }

            }

            // remove all files in the web folder
            Filesystem::recursiveUnlink($folder);
        }

        // create the web folder and copy the contents
        mkdir($folder);
        Filesystem::recursiveCopy($this->getFile() . "resources/web", $folder);
    }

    public function getWebFolder(): string
    {
        return $this->getDataFolder() . "web/";
    }

    public function getBackupFolder(): string
    {
        return $this->getDataFolder() . "backup/";
    }

    /**
     * Load and generate the web config
     * @return void
     */
    public function loadWebConfig(): void
    {
        $apiSettings = $this->settingsManager->getApi();
        $visibleWorlds = $apiSettings->getWorlds();
        $mapConfig = $apiSettings->getMapConfig();
        $worldSettings = $mapConfig->getWorldSettings();

        // determine the default world
        $defaultWorld = $mapConfig->getDefaultWorld();
        if (strlen($defaultWorld) == 0) {
            $defaultWorld = $this->getServer()->getConfigGroup()->getConfigString(ServerProperties::DEFAULT_WORLD_NAME, "world");
        }

        // not all worlds are visible
        if (count($visibleWorlds) > 0) {
            // default world is not visible on the map, set default as first in the visible list
            if (!in_array($defaultWorld, $visibleWorlds)) {
                $defaultWorld = $visibleWorlds[0];
            }

            // remove all settings of invisible worlds
            foreach ($this->getWorldNames() as $worldName) {
                // we aren't allowed to render this world
                if (!in_array($worldName, $visibleWorlds, true) && array_key_exists($worldName, $worldSettings)) {
                    // remove from the world from the settings list
                    unset($worldSettings[$worldName]);
                }
            }
        }

        // create the new map config
        $mapConfig = new MapConfig($defaultWorld, $mapConfig->getDefaultSettings(), $worldSettings);
        // write to the config file
        file_put_contents($this->getWebFolder() . "config.json", json_encode($mapConfig));
    }

    /**
     * Load all terrain textures
     * TODO generate tmp model texture files
     * @return void
     */
    private function loadTerrainTextures(): void
    {
        $textureSettings = $this->settingsManager->getTexture();

        // get the fallback block
        $fallbackBlockId = $textureSettings->getFallback();
        $fallbackBlock = StringToItemParser::getInstance()->parse($fallbackBlockId)?->getBlock() ?? null;

        // get the height overlay data
        $heightColor = $textureSettings->getHeightColor();
        $heightAlpha = $textureSettings->getHeightAlpha();

        $options = new TerrainTexturesOptions($fallbackBlock, $heightColor, $heightAlpha);

        $path = $this->getResourcePacksFolder();

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

    /**
     * @throws HookAlreadyRegistered
     */
    private function registerLibraries(): void
    {
        if (!PacketHooker::isRegistered()) PacketHooker::register($this);
    }

    /**
     * Start all scheduler tasks
     * @return void
     */
    private function startTasks(): void
    {
        // start the render scheduler
        $this->renderScheduler = new RenderSchedulerTask($this);
        $this->getScheduler()->scheduleRepeatingTask($this->renderScheduler, $this->settingsManager->getScheduler()->getPeriod());

        // start the chunk update task, this checks every period if regions have to be updated
        $this->chunkScheduler = new ChunkSchedulerTask($this);
        $this->getScheduler()->scheduleRepeatingTask($this->chunkScheduler, $this->settingsManager->getChunkScheduler()->getPeriod());

        // start the api update task
        $updateApiTask = new UpdateApiTask($this);
        $this->getScheduler()->scheduleRepeatingTask($updateApiTask, $this->settingsManager->getApi()->getPeriod());

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
     * Start the web server
     * @return void
     */
    private function startWebServer(): void
    {

        try {
            // create the web server
            $this->webServer->createServer();
            // start the web server
            $this->webServer->startServer();
        } catch (Exception $e) {
            $this->disable("Could not start the web server.", $e->getMessage());
            return;
        }
    }

    /**
     * Debug function which prints all registered block state textures
     * @return void
     */
    private function enableDebug(): void
    {
        $this->getLogger()->notice("Debug mode enabled");

        // list with not found type ids
        // this is used so that not all blocks with the same type id will be logged which causes unnecessary spam
        $notFound = [];
        foreach (RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $block) {
            $texture = $this->terrainTextures->getTextureByBlock($block);

            $id = $block->getTypeId();
            if ($texture === null && !in_array($id, $notFound, true)) {
                $notFound[] = $id;
                $textureName = TextureUtils::getBlockTextureName($block);

                // don't warn for ignored textures, as they will never have a texture
                if (!in_array($textureName, self::IGNORED_TEXTURES, true)) {
                    $this->getLogger()->warning("Cannot find texture of block: " . $block->getName() . ", ID: " . $block->getTypeId() . ", Texture: " . $textureName);
                }
            }
        }

        if (sizeof($notFound) <= sizeof(self::IGNORED_TEXTURES)) {
            $this->getLogger()->notice("All textures have been registered");
        }


        $blocks = VanillaBlocks::getAll();

        foreach ($blocks as $block) {
            $texture = $this->terrainTextures->getTextureByBlock($block);
            if ($texture !== null) $this->getLogger()->info("Found Texture: $texture, for block: {$block->getName()}, using name: " . TextureUtils::getBlockTextureName($block));
            else $this->getLogger()->warning("No Texture found for block: {$block->getName()}, using name: " . TextureUtils::getBlockTextureName($block));
        }
    }

    protected function onDisable(): void
    {
        // store the markers
        $this->markers->storeMarkers();
        // close the socket
        $this->webServer->stopServer();
    }

}