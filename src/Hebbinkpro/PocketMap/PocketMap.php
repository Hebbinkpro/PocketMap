<?php

namespace Hebbinkpro\PocketMap;

use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\WebServer\http\HttpResponse;
use Hebbinkpro\WebServer\route\Route;
use Hebbinkpro\WebServer\route\Router;
use Hebbinkpro\WebServer\WebServer;
use Hebbinkpro\PocketMap\render\WorldRenderer;
use Hebbinkpro\PocketMap\task\AsyncLevelDBTask;
use Hebbinkpro\PocketMap\task\ChunkUpdateTask;
use Hebbinkpro\PocketMap\task\RenderSchedulerTask;
use Hebbinkpro\PocketMap\utils\ResourcePack;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\Filesystem;
use pocketmine\world\World;

class PocketMap extends PluginBase implements Listener
{
    public const RESOURCE_PACK_PATH = "resource_packs/";
    public const RESOURCE_PACK_NAME = "v1.20.0.1";
    public const TEXTURE_SIZE = 16;
    public const RENDER_PATH = "renders/";

    private static string $tmpDataPath;

    private ResourcePack $resourcePack;
    private WebServer $webServer;

    private RenderSchedulerTask $renderScheduler;
    private ChunkUpdateTask $chunkUpdateTask;

    /** @var WorldRenderer[] */
    private array $worldRenderers = [];

    public function getWorldRenderer(World|string $world): ?WorldRenderer
    {
        if (is_string($world)) $worldName = $world;
        else $worldName = $world->getFolderName();

        return $this->worldRenderers[$worldName] ?? null;
    }

    public function createWorldRenderer(World $world): WorldRenderer
    {
        $path = $this->getDataFolder() . PocketMap::RENDER_PATH . $world->getFolderName() . "/";
        $renderer = new WorldRenderer($world, $this->getResourcePack(), $path, $this->getRenderScheduler());
        $this->worldRenderers[$world->getFolderName()] = $renderer;
        return $renderer;
    }

    /**
     * @return ResourcePack
     */
    public function getResourcePack(): ResourcePack
    {
        return $this->resourcePack;
    }

    /**
     * @return RenderSchedulerTask
     */
    public function getRenderScheduler(): RenderSchedulerTask
    {
        return $this->renderScheduler;
    }

    public function removeWorldRenderer(World $world): void
    {
        unset($this->worldRenderers[$world->getFolderName()]);
    }

    /**
     * @return ChunkUpdateTask
     */
    public function getChunkUpdateTask(): ChunkUpdateTask
    {
        return $this->chunkUpdateTask;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch ($command->getName()) {
            case "reload":
                $this->getLogger()->info("Reloading all config files...");
                $this->loadResources(true);
                $this->getLogger()->info("All config files are reloaded");
                break;

            default:
                return false;
        }

        return true;
    }

    private function loadResources($reloadWebFiles = false): void
    {
        // save the config file
        $this->saveDefaultConfig();

        // get startup settings
        $startupSettings = $this->getConfig()->get("startup", ["reload-web-files" => false]);

        $pluginResources = $this->getFile() . "resources/";
        $data = $this->getDataFolder();

        // load the resource pack files
        $resourcePacks = "resource_packs/";
        $defaultPack = $resourcePacks . self::RESOURCE_PACK_NAME;
        if (!is_dir($data . $resourcePacks) || !is_dir($data . $defaultPack)) {
            Filesystem::recursiveCopy($pluginResources . $resourcePacks, $data . $resourcePacks);
        }

        // removes existing web files on startup
        if ($startupSettings["reload-web-files"] || $reloadWebFiles) {
            // reload web files on startup
            Filesystem::recursiveUnlink($this->getDataFolder() . "web");
        }

        // load the web server files
        $web = "web/";
        if (!is_dir($data . $web)) {
            Filesystem::recursiveCopy($pluginResources . $web, $data . $web);
        }

        // create the renders folder
        $renders = "renders/";
        if (!is_dir($data . $renders)) {
            mkdir($data . $renders);
        }

        // create the tmp folder for storing temp data

        self::$tmpDataPath = $data."tmp/";
        if (!is_dir(self::$tmpDataPath)) {
            mkdir(self::$tmpDataPath);
        }


        // create the regions folder inside tmp
        $tmpRegions = "regions/";
        if (!is_dir(self::$tmpDataPath.$tmpRegions)) {
            mkdir(self::$tmpDataPath.$tmpRegions);
        }


        // create render folders for each world
        $worldFolders = scandir($this->getServer()->getDataPath() . "worlds/");
        foreach ($worldFolders as $worldName) {
            if (!is_dir($data . $renders . $worldName)) {
                mkdir($data . $renders . $worldName);
            }
            if (!is_dir(self::$tmpDataPath . $tmpRegions . $worldName)) {
                mkdir(self::$tmpDataPath . $tmpRegions . $worldName);
            }
        }
    }

    protected function onEnable(): void
    {
        // load all resources
        $this->loadResources();

        // create the resource pack instance
        $this->resourcePack = new ResourcePack($this->getDataFolder() . self::RESOURCE_PACK_PATH . self::RESOURCE_PACK_NAME . "/", self::TEXTURE_SIZE);

        WebServer::register($this);

        // create the web server
        $this->createWebServer();

        // start the render scheduler
        $this->renderScheduler = new RenderSchedulerTask($this);
        $this->getScheduler()->scheduleRepeatingTask($this->renderScheduler, 1);

        // start the chunk update task, this check every period if regions have to be updated
        $this->chunkUpdateTask = new ChunkUpdateTask($this);
        $this->getScheduler()->scheduleRepeatingTask($this->chunkUpdateTask, 100);

        // register the event listener
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    private function createWebServer(): void
    {
        $webSettings = $this->getConfig()->get("web-server", [
            "address" => "127.0.0.1",
            "port" => 3000
        ]);

        // create the web server
        $this->webServer = new WebServer($webSettings["address"], $webSettings["port"]);
        $router = $this->webServer->getRouter();

        // main route
        $router->get("/", function (HttpRequest $req, HttpResponse $res, mixed ...$params) {
            $res->sendFile($params[0]."web/pages/index.html");
            $res->end();
        }, $this->getDataFolder());

        // all static files used by web pages
        $web = $this->getDataFolder() . "web";
        $router->getStatic("/static", "$web/static");

        // register the api router
        $router->route("/api/pocketmap", $this->registerApiRoutes());

        // start the web server
        $this->webServer->start();
    }

    private function registerApiRoutes(): Router
    {
        $router = new Router();

        $router->get("/", function (HttpRequest $req, HttpResponse $res) {
            $res->send("Hello World", "text/plain");
            $res->end();
        });

        $router->get("/regions", function (HttpRequest $req, HttpResponse $res, ...$params) {

            $worlds = array_diff(scandir($params[0] . "renders"), [".", ".."]);

            $res->json([
                "worlds" => array_values($worlds)
            ]);
            $res->end();
        }, $this->getDataFolder());

        // get image renders
        $router->getStatic("/render", $this->getDataFolder() . "renders");

        return $router;
    }


    protected function onDisable(): void
    {
        // close the socket
        $this->webServer->close();
    }

    public static function getTmpDataPath(): string {
        return self::$tmpDataPath;
    }
}