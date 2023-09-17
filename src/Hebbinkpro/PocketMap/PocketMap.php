<?php

namespace Hebbinkpro\PocketMap;

use Exception;
use Hebbinkpro\PocketMap\api\UpdateApiTask;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\scheduler\ChunkSchedulerTask;
use Hebbinkpro\PocketMap\scheduler\RenderSchedulerTask;
use Hebbinkpro\PocketMap\textures\TerrainTextures;
use Hebbinkpro\PocketMap\textures\TerrainTexturesOptions;
use Hebbinkpro\PocketMap\utils\ConfigManager;
use Hebbinkpro\WebServer\exception\WebServerException;
use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\http\HttpResponse;
use Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Hebbinkpro\WebServer\route\Router;
use Hebbinkpro\WebServer\WebServer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use pocketmine\world\World;

class PocketMap extends PluginBase implements Listener
{
    public const CONFIG_VERSION = 1.5;

    public const RESOURCE_PACK_NAME = "v1.20.10.1";
    public const TEXTURE_SIZE = 16;

    private static PocketMap $instance;
    /** @var WorldRenderer[] */
    private static array $worldRenderers = [];
    private ConfigManager $configManager;
    private TerrainTextures $terrainTextures;
    private WebServer $webServer;
    private RenderSchedulerTask $renderScheduler;
    private ChunkSchedulerTask $chunkRenderer;

    public static function getConfigManger(): ConfigManager
    {
        return self::$instance->configManager;
    }

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
     * Create a world renderer for a given world
     * @param World $world
     * @return WorldRenderer
     */
    public function createWorldRenderer(World $world): WorldRenderer
    {
        $path = $this->getDataFolder() . "renders/" . $world->getFolderName() . "/";
        $renderer = new WorldRenderer($world, $this->getTerrainTextures(), $path, $this->getRenderScheduler(), $this->chunkRenderer);
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
     * Get the chunk renderer
     * @return ChunkSchedulerTask
     */
    public function getChunkRenderer(): ChunkSchedulerTask
    {
        return $this->chunkRenderer;
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

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch ($command->getName()) {
            case "reload":
                $this->getLogger()->info("Reloading all config files...");
                $this->loadConfig();
                $this->getLogger()->info("All config files are reloaded");
                break;

            default:
                return false;
        }

        return true;
    }

    /**
     * Load the config
     * @return void
     */
    private function loadConfig(): void
    {
        $folder = $this->getDataFolder();

        // save the config file
        $this->saveDefaultConfig();

        $config = $this->getConfig();
        $version = $config->get("version", -1.0);
        if ($version != self::CONFIG_VERSION) {
            $this->getLogger()->notice("The current version of PocketMap is using another config version.");
            $this->getLogger()->info("You can find your old config in 'config_v$version.yml'");
            $this->getLogger()->warning("Replacing 'config.yml v$version' with 'config.yml v" . self::CONFIG_VERSION . "'");

            // clone all contents from config.yml inside the backup config
            file_put_contents($folder . "config_v$version.yml",
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

    protected function onEnable(): void
    {
        self::$instance = $this;

        // load all resources
        $this->loadConfig();
        $this->generateFolderStructure();

        // load the terrain textures
        $this->loadTerrainTextures();

        WebServer::register($this);

        try {
            // create the web server
            $this->createWebServer();
        } catch (Exception $e) {
            $this->getLogger()->alert("Could not start the web server.");
            $this->getLogger()->error($e);
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        // start the render scheduler
        $this->renderScheduler = new RenderSchedulerTask($this);
        $this->getScheduler()->scheduleRepeatingTask($this->renderScheduler, $this->configManager->getInt("renderer.scheduler.run-period", 5));

        // start the chunk update task, this check every period if regions have to be updated
        $this->chunkRenderer = new ChunkSchedulerTask($this);
        $this->getScheduler()->scheduleRepeatingTask($this->chunkRenderer, $this->configManager->getInt("renderer.chunk-renderer.run-period", 10));

        // start the api update task
        $updateApiTask = new UpdateApiTask($this, $this->getDataFolder() . "tmp/api/");
        $this->getScheduler()->scheduleRepeatingTask($updateApiTask, $this->configManager->getInt("api.update-period", 20));

        // register the event listener
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    private function generateFolderStructure(): void
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

        if (!is_dir($folder . "tmp")) {
            mkdir($folder . "tmp");
        }

        // create the regions folder inside tmp
        if (!is_dir($folder . "tmp/regions")) {
            mkdir($folder . "tmp/regions");
        }

        // create render folders for each world
        $worldFolders = scandir($this->getServer()->getDataPath() . "worlds/");
        foreach ($worldFolders as $worldName) {
            // it's not a world
            if (!is_dir($this->getServer()->getDataPath() . "worlds/$worldName")
                || !in_array("level.dat", scandir($this->getServer()->getDataPath() . "worlds/$worldName"))) {
                continue;
            }

            if (!is_dir($folder . "renders/$worldName")) {
                mkdir($folder . "renders/$worldName");
            }
        }

        if (!is_dir($folder . "web")) {
            mkdir($folder . "web");
            Filesystem::recursiveCopy($this->getFile() . "resources/web", $this->getDataFolder() . "web");
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

        // get the fallback block
        $fallbackBlockId = $textureSettings->getString("fallback-block", "minecraft:bedrock");
        $fallbackBlock = StringToItemParser::getInstance()->parse($fallbackBlockId)?->getBlock() ?? null;

        // get the height overlay data
        $heightColor = $textureSettings->getInt("height-overlay.color", 0x000000);
        $heightAlpha = $textureSettings->getInt("height-overlay.alpha", 3);

        $options = new TerrainTexturesOptions($fallbackBlock, $heightColor, $heightAlpha);

        $path = $this->getDataFolder() . "resource_packs/";

        $this->terrainTextures = TerrainTextures::generate($this, $path, $options);
    }

    /**
     * Create the web server and set the routes
     * @throws PhpVersionNotSupportedException
     * @throws WebServerException
     */
    private function createWebServer(): void
    {
        $webFolder = $this->getDataFolder() . "web/";

        $webSettings = $this->configManager->getManager("web-server", true, ["address" => "127.0.0.1", "port" => 3000]);

        // create the web server
        $this->webServer = new WebServer($webSettings->getString("address", "127.0.0.1"), $webSettings->getInt("port", 3000));
        $router = $this->webServer->getRouter();

        // main route
        $router->getFile("/", $webFolder . "pages/index.html");

        // all static files used by web pages
        $router->getStatic("/static", $webFolder . "static");

        // register the api router
        $router->route("/api/pocketmap", $this->registerApiRoutes());

        // start the web server
        $this->webServer->start();
    }

    /**
     * Register all API routes to the web server
     * @throws PhpVersionNotSupportedException
     * @throws WebServerException
     */
    private function registerApiRoutes(): Router
    {
        $apiFolder = $this->getDataFolder() . "tmp/api/";

        $router = new Router();

        $router->get("/", function (HttpRequest $req, HttpResponse $res) {
            $res->send("Hello World", "text/plain");
            $res->end();
        });

        // get the world data
        $router->getFile("/worlds", $apiFolder . "worlds.json", "[]");

        // get player data
        $router->getFile("/players", $apiFolder . "players.json", "[]");

        // get the player heads
        $router->getStatic("/players/skin", $apiFolder . "skin");

        // get image renders
        $router->getStatic("/render", $this->getDataFolder() . "renders");

        return $router;
    }

    protected function onDisable(): void
    {
        // close the socket
        if ($this->webServer->isStarted()) $this->webServer->close();
    }
}