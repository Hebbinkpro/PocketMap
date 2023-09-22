<?php

namespace Hebbinkpro\PocketMap\textures;

use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\utils\block\BlockDataValues;
use Hebbinkpro\PocketMap\utils\ResourcePackUtils;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePackException;
use pocketmine\resourcepacks\ResourcePackManager;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\Filesystem;
use ZipArchive;

class TerrainTextures extends ResourcePackTextures
{
    public const TERRAIN_TEXTURES = "terrain_textures.json";

    private string $path;
    private TerrainTexturesOptions $options;

    private array $packs;

    private function __construct(string $path, TerrainTexturesOptions $options)
    {
        parent::__construct();
        $this->path = $path;
        $this->options = $options;
        $this->packs = [];
    }

    /**
     * Generate a (new) TerrainTextures instance
     * @param PluginBase $plugin
     * @param string $path
     * @param TerrainTexturesOptions $options
     * @return TerrainTextures
     */
    public static function generate(PluginBase $plugin, string $path, TerrainTexturesOptions $options): TerrainTextures
    {
        $lastTerrainTextures = self::fromExistingTextures($path, $options);
        $textures = new TerrainTextures($path, $options);

        $packs = $textures->getAllResourcePacks($plugin->getServer()->getResourcePackManager(), $lastTerrainTextures);

        // pack list does not exist
        if ($lastTerrainTextures === null || $lastTerrainTextures->getPacks() !== $packs) {
            $textures->indexBlocks($packs);
        }

        $textures->loadFromFile();
        return $textures;
    }

    /**
     * Get a TerrainTextures instance from the given path
     * @param string $path
     * @param TerrainTexturesOptions $options
     * @return TerrainTextures|null
     */
    public static function fromExistingTextures(string $path, TerrainTexturesOptions $options): ?TerrainTextures
    {
        if (!is_file($path . self::TERRAIN_TEXTURES)) return null;

        $textures = new TerrainTextures($path, $options);
        $textures->loadFromFile();
        return $textures;
    }

    /**
     * Loads all data from the terrain_textures file in memory
     * @return void
     */
    private function loadFromFile(): void
    {
        if (!is_file($this->path . self::TERRAIN_TEXTURES)) return;

        $contents = json_decode(file_get_contents($this->path . self::TERRAIN_TEXTURES), true) ?? [];
        if (empty($contents)) return;

        $this->packs = $contents["packs"];
        $this->textures = $contents["textures"];
        $this->terrainTextures = $contents["terrain_textures"];
        $this->blocks = $contents["blocks"];
    }

    /**
     * Extract all resource packs inside the server's resource_packs folder
     * @param ResourcePackManager $manager
     * @param TerrainTextures|null $lastTerrainTextures
     * @return array
     */
    private function getAllResourcePacks(ResourcePackManager $manager, ?TerrainTextures $lastTerrainTextures = null): array
    {
        if (!is_dir($this->path)) mkdir($this->path);

        $lastLoaded = [];
        if ($lastTerrainTextures !== null) $lastLoaded = $lastTerrainTextures->getPacks()["resource_packs"] ?? [];

        $loaded = [];

        $packs = $manager->getResourceStack();
        foreach ($packs as $pack) {
            // get the zipped resource pack
            $uuid = $pack->getPackId();
            if (!$pack instanceof ZippedResourcePack) continue;

            $key = $manager->getPackEncryptionKey($uuid);

            $filePath = explode("/", $pack->getPath());

            $info = [
                "uuid" => $pack->getPackId(),
                "file" => $filePath[array_key_last($filePath)],
                "version" => $pack->getPackVersion(),
                "sha256" => utf8_encode($pack->getSha256())
            ];

            // this pack is already loaded in a previous startup
            if (in_array($info, $lastLoaded)) {
                $loaded[$uuid] = $info;
                continue;
            }

            if ($this->extractResourcePack($pack, $key)) {
                $loaded[$uuid] = $info;
            }
        }

        foreach (scandir($this->path) as $file) {
            $path = $this->path . $file;
            // not a dir or the current vanilla resource pack
            if (in_array($file, [".", "..", PocketMap::RESOURCE_PACK_NAME]) || !is_dir($path)) continue;

            // remove unused dirs
            if (!array_key_exists($file, $loaded)) Filesystem::recursiveUnlink($path);
        }

        return [
            "vanilla" => PocketMap::RESOURCE_PACK_NAME,
            "resource_packs" => $loaded
        ];
    }

    public function getPacks(): array
    {
        return $this->packs;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Extracts the given resource pack from the resource_packs folder
     * @param ZippedResourcePack $pack the pack to extract
     * @param string|null $key the encryption key
     * @return bool if the extraction was successful
     */
    private function extractResourcePack(ZippedResourcePack $pack, string $key = null): bool
    {
        // TODO: encrypted packs
        if ($key !== null) return false;
        $uuid = $pack->getPackId();


        // open the zip archive
        $archive = new ZipArchive();
        if (($openResult = $archive->open($pack->getPath())) !== true) {
            throw new ResourcePackException("Encountered ZipArchive error code $openResult while trying to open {$pack->getPath()}");
        }


        $rpPath = $this->path . $uuid . "/";
        if (!is_dir($rpPath)) mkdir($rpPath . ResourcePackUtils::BLOCK_TEXTURES, 0777, true);

        $prefix = ResourcePackUtils::getPrefix($archive);

        $manifest = $archive->getFromName($prefix . ResourcePackUtils::MANIFEST);

        file_put_contents($rpPath . ResourcePackUtils::MANIFEST, $manifest);

        $blocks = $archive->getFromName($prefix . ResourcePackUtils::BLOCKS);
        if ($blocks !== false) file_put_contents($rpPath . ResourcePackUtils::BLOCKS, $blocks);

        $terrainTexture = $archive->getFromName($prefix . ResourcePackUtils::TERRAIN_TEXTURE);
        if ($terrainTexture !== false) file_put_contents($rpPath . ResourcePackUtils::TERRAIN_TEXTURE, $terrainTexture);

        $blockTextures = ResourcePackUtils::getAllBlockTextures($archive, $prefix);

        // store all textures
        foreach ($blockTextures as $path) {
            $texture = $archive->getFromName($prefix . $path);
            if ($texture !== false) {
                $blockPath = str_replace($rpPath . ResourcePackUtils::BLOCK_TEXTURES, "", $rpPath . $path);
                if (($parts = explode("/", $blockPath)) > 1) {
                    array_pop($parts);
                    $blockFolderPath = $rpPath . ResourcePackUtils::BLOCK_TEXTURES . implode("/", $parts);
                    if (!is_dir($blockFolderPath)) mkdir($blockFolderPath, 0777, true);
                }

                file_put_contents($rpPath . $path, $texture);
            }
        }

        // close the archive
        $archive->close();

        return true;
    }

    private function indexBlocks(array $packs): void
    {
        // load the vanilla textures
        $textures = ResourcePackTextures::getFromPath($this->path . PocketMap::RESOURCE_PACK_NAME . "/");

        foreach ($packs["resource_packs"] as $packInfo) {
            $uuid = $packInfo["uuid"];

            // get the textures inside the pack
            $packTextures = ResourcePackTextures::getFromPath($this->path . $uuid . "/", $uuid);

            // merge the textures inside the pack with the existing textures
            $textures->merge($packTextures);
        }

        // generate the block index structure
        $terrainTextures = [
            "packs" => $packs,
            "textures" => $textures->getTextures(),
            "terrain_textures" => $textures->getTerrainTextures(),
            "blocks" => $textures->getBlocks()
        ];

        // store the block index
        file_put_contents($this->path . self::TERRAIN_TEXTURES, json_encode($terrainTextures));
    }

    public function getVanillaPath(): ?string
    {
        return $this->path . PocketMap::RESOURCE_PACK_NAME . "/";
    }

    public function getBlockTexturePath(Block $block): ?string
    {
        $textureName = TextureUtils::getBlockTextureName($block);
        if ($textureName === null) return null;

        $texture = $this->getTextureByBlock($block);
        return $this->getRealTexturePath($texture);
    }

    public function getTextureByBlock(Block $block): string|null
    {
        $textureName = TextureUtils::getBlockTextureName($block);

        $blockTexture = $this->getBlockByName($textureName);
        if ($blockTexture !== null) {
            if (is_array($blockTexture)) {
                // it has directions
                $face = TextureUtils::getBlockFaceTexture($block, array_keys($blockTexture));
                if ($face === null) return null;

                $blockTexture = $blockTexture[$face];
            }

            $textureName = $blockTexture;
        }

        $terrainTexture = $this->getTerrainTextureByName($textureName);
        if ($terrainTexture !== null) {
            if (is_array($terrainTexture)) {
                // we need a block data value
                $dataValue = BlockDataValues::getDataValue($block);
                $terrainTexture = $terrainTexture[$dataValue] ?? null;
                if ($terrainTexture === null) return null;
            }

            $textureName = $terrainTexture;
        }

        return $this->getTextureByName($textureName);
    }

    /**
     * Get the real path to a texture file
     * @param string $path
     * @return string
     */
    public function getRealTexturePath(string $path): string
    {
        // vanilla textures don't have a prefix, so we have to add it
        if (str_starts_with($path, "textures/")) return $this->path . PocketMap::RESOURCE_PACK_NAME . "/" . $path;
        // texture path is already valid
        return $this->path . $path;
    }

    /**
     * @return TerrainTexturesOptions
     */
    public function getOptions(): TerrainTexturesOptions
    {
        return $this->options;
    }
}