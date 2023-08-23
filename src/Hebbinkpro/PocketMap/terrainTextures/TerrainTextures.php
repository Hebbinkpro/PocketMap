<?php

namespace Hebbinkpro\PocketMap\terrainTextures;

use CustomiesExample\block\ExampleBlock;
use Hebbinkpro\PocketMap\PocketMap;
use Hebbinkpro\PocketMap\utils\block\BlockDataValues;
use Hebbinkpro\PocketMap\utils\TextureUtils;
use pocketmine\block\Block;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePackException;
use pocketmine\resourcepacks\ResourcePackManager;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\Filesystem;
use ZipArchive;

class TerrainTextures
{
    private static array $blockIndex = [];

    private string $path;
    private TerrainTexturesOptions $options;
    private bool $cache;

    private function __construct(string $path, TerrainTexturesOptions $options, bool $cache = true)
    {
        $this->path = $path;
        $this->options = $options;
        $this->cache = $cache;

        if ($this->cache) $this->getTerrainTextures();
    }

    /**
     * Get the block index.
     * When cache is enabled, the index will be stored and every call to this function will return the stored value
     * @return array
     */
    public function getTerrainTextures(): array
    {
        if (!$this->cache) return json_decode(file_get_contents($this->path . "terrain_textures.json"), true);

        if (empty(self::$blockIndex) && is_file($this->path . "terrain_textures.json")) {
            self::$blockIndex = json_decode(file_get_contents($this->path . "terrain_textures.json"), true);
        }
        return self::$blockIndex;
    }

    public static function generate(PluginBase $plugin, string $path, TerrainTexturesOptions $options): TerrainTextures
    {
        $lastTerrainTextures = self::fromExistingTextures($path, $options);
        $packs = self::extractResourcePacks($path, $plugin->getServer()->getResourcePackManager(), $lastTerrainTextures);
        self::indexBlocks($path, $packs, $lastTerrainTextures);

        return new TerrainTextures($path, $options);
    }

    public static function fromExistingTextures(string $path, TerrainTexturesOptions $options): ?TerrainTextures
    {
        if (!is_dir($path) || !in_array("block_index.json", scandir($path))) return null;
        return new TerrainTextures($path, $options);
    }

    /**
     * Extract all resource packs inside the server's resource_packs folder
     * @param string $rpPath
     * @param ResourcePackManager $manager
     * @param TerrainTextures|null $lastTerrainTextures
     * @return array
     */
    private static function extractResourcePacks(string $rpPath, ResourcePackManager $manager, ?TerrainTextures $lastTerrainTextures = null): array
    {
        if (!is_dir($rpPath)) mkdir($rpPath);

        $lastLoaded = [];
        if ($lastTerrainTextures !== null) $lastTerrainTextures->getPacks() ?? [];

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

            if (self::extractResourcePack($pack, $rpPath, $key)) {
                $loaded[$uuid] = $info;
            }
        }

        foreach (scandir($rpPath) as $file) {
            $path = $rpPath . $file;
            // not a dir or the current vanilla resource pack
            if (in_array($file, [".", "..", PocketMap::RESOURCE_PACK_NAME]) || !is_dir($path)) continue;

            // remove unused dirs
            if (!array_key_exists($file, $loaded)) Filesystem::recursiveUnlink($path);
        }

        return $loaded;
    }

    public function getPacks(): array
    {
        return $this->getTerrainTextures()["packs"] ?? [];
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
     * @param string $path path to the resource pack folder in plugin_data
     * @param string|null $key the encryption key
     * @return bool if the extraction was successful
     */
    private static function extractResourcePack(ZippedResourcePack $pack, string $path, string $key = null): bool
    {
        // TODO: encrypted packs
        if ($key !== null) return false;
        $uuid = $pack->getPackId();

        // open the zip archive
        $archive = new ZipArchive();
        if (($openResult = $archive->open($pack->getPath())) !== true) {
            throw new ResourcePackException("Encountered ZipArchive error code $openResult while trying to open {$pack->getPath()}");
        }

        $rpPath = $path . $uuid . "/";
        if (!is_dir($rpPath)) mkdir($rpPath . "textures/blocks", 0777, true);

        $blocks = $archive->getFromName("manifest.json");
        if ($blocks !== false) file_put_contents($rpPath . "manifest.json", $blocks);
        $blocks = $archive->getFromName("blocks.json");
        if ($blocks !== false) file_put_contents($rpPath . "blocks.json", $blocks);
        $terrainTexture = $archive->getFromName("textures/terrain_texture.json");
        if ($terrainTexture !== false) file_put_contents($rpPath . "textures/terrain_texture.json", $terrainTexture);

        $texturePaths = [];

        // get all texture paths given in the terrain texture file
        $terrainTextureData = json_decode($terrainTexture, true);
        foreach ($terrainTextureData["texture_data"] as $block => $blockData) {
            $textures = $blockData["textures"];
            if (is_string($textures)) $texturePaths[] = $textures;
            else if (is_array($textures)) {
                if (isset($textures["path"])) $texturePaths[] = $textures["path"];
                else {
                    foreach ($textures as $path) {
                        $texturePaths[] = $path;
                    }
                }
            }
        }

        // remove all .png/.tga file extensions from the path names
        $texturePaths = str_replace([".png", ".tga"], "", $texturePaths);

        // remove all duplicates (if they exist)
        $texturePaths = array_unique($texturePaths);

        // store all textures
        foreach ($texturePaths as $path) {
            $ext = "png";
            $texture = $archive->getFromName("$path.png");

            // it is possible that some textures use tga instead of png, but it's not that common
            if ($texture === false) {
                $ext = "tga";
                $texture = $archive->getFromName("$path.tga");
            }

            if ($texture !== false) file_put_contents($rpPath . "$path.$ext", $texture);
        }

        // close the archive
        $archive->close();

        return true;
    }

    private static function indexBlocks(string $path, array $resourcePacks, ?TerrainTextures $lastTerrainTextures = null): void
    {
        $packs = [
            "vanilla" => PocketMap::RESOURCE_PACK_NAME,
            "resource_packs" => $resourcePacks
        ];

        // packs list is the same
        if ($lastTerrainTextures !== null && $lastTerrainTextures->getPacks() === $packs) return;


        // create the vanilla index
        $index = self::getResourcePackBlockIndex($path . PocketMap::RESOURCE_PACK_NAME . "/");

        // loop through all the other resource packs
        $noOverwrite = [];
        foreach ($packs["resource_packs"] as $packInfo) {
            $uuid = $packInfo["uuid"];
            // generate the pack index
            $packIndex = self::getResourcePackBlockIndex($path . $uuid . "/");
            // merge the pack index onto the other packs
            // when a texture is already overwritten by another pack, the texture will remain that of the other pack
            self::mergeResourcePackBlockIndex($index, $packIndex, $uuid, $noOverwrite);
            $noOverwrite[] = $uuid;
        }

        // generate the block index structure
        $terrainTextures = [
            "packs" => $packs,
            "blocks" => $index
        ];

        // store the block index
        file_put_contents($path . "terrain_textures.json", json_encode($terrainTextures));
    }

    private static function getResourcePackBlockIndex(string $path): array
    {
        $blocksTextures = [];
        if (is_file($path . "blocks.json")) {
            $contents = json_decode(file_get_contents($path . "blocks.json"), true) ?? [];

            foreach ($contents as $name => $data) {
                if (!isset($data["textures"])) continue;
                $textures = $data["textures"];

                if (is_string($textures)) $blocksTextures[$name] = $textures;
                if (is_array($textures)) {
                    $blocksTextures[$name] = [];
                    foreach ($textures as $key => $textureName) {
                        $blocksTextures[$name][$key] = $textureName;
                    }
                }
            }
        }

        // set all contents of terrain_textures.json to the index
        $index = [];
        if (is_file($path . "textures/terrain_texture.json")) {
            $contents = json_decode(file_get_contents($path . "textures/terrain_texture.json"), true) ?? [];

            foreach ($contents["texture_data"] as $name => $data) {
                if (!isset($data["textures"])) continue;
                $textures = $data["textures"];

                if (is_string($textures)) $index[$name] = self::cleanTexturePath($textures);
                if (is_array($textures) && !empty($textures)) {
                    if (isset($textures["path"])) {
                        $textures["path"] = self::cleanTexturePath($textures["path"]);
                        $index[$name] = $textures;
                        continue;
                    }

                    $index[$name] = [];
                    foreach ($textures as $key => $texture) {
                        if (is_string($texture)) $index[$name][$key] = self::cleanTexturePath($texture);
                        if (is_array($texture) && isset($texture["path"])) {
                            $texture["path"] = self::cleanTexturePath($texture["path"]);
                            $index[$name][$key] = $texture;
                        }
                    }
                }
            }
        }


        // check all blocks.json entries and map them to the correct textures
        $replacements = [];
        foreach ($blocksTextures as $name => $texture) {

            if (array_key_exists($name, $index)) {
                $replacements[$name] = $index[$name];
                unset($index[$name]);
            }

            if (is_string($texture)) {
                if (array_key_exists($name, $replacements) && $name === $texture) {
                    $index[$name] = $replacements[$name];
                } else if (array_key_exists($texture, $index)) {
                    $index[$name] = $index[$texture];
                }
            }

            if (is_array($texture)) {
                $index[$name] = [];
                foreach ($texture as $key => $textureName) {
                    if (array_key_exists($name, $replacements) && $name === $textureName) {
                        $index[$name][$key] = $replacements[$textureName];
                    } else if (array_key_exists($textureName, $index)) {
                        $index[$name][$key] = $index[$textureName];
                    }

                }

                if (isset($index[$name]) && empty($name)) unset($index[$name]);
            }
        }

        return $index;
    }

    /**
     * Clean a texture path by removing any extensions at the end of it
     * @param string $texturePath
     * @return string
     */
    public static function cleanTexturePath(string $texturePath): string {

        $parts = explode("/", $texturePath);
        $last = $parts[array_key_last($parts)];

        if (str_contains($last, ".")) {
            $parts[array_key_last($parts)] = explode(".", $last)[0];
        }

        return implode("/", $parts);
    }

    /**
     * Merges the source index into the destination index
     * @param array $dst the destination index
     * @param array $src the source index
     * @param string $prefix the prefix to add to texture names of src
     * @param array $noOverwrite all prefixes that cannot be overridden
     * @return void
     */
    private static function mergeResourcePackBlockIndex(array &$dst, array $src, string $prefix, array $noOverwrite = []): void
    {

        foreach ($src as $name => $texture) {
            $texture = "$prefix/$texture";

            // texture does not exist
            if (!array_key_exists($name, $dst)) {
                $dst[$name] = $texture;
                continue;
            }

            $destTexture = $dst[$name];

            if (is_string($destTexture)) {
                if (self::canOverwrite($destTexture, $noOverwrite)) $dst[$name] = $texture;
            } else if (is_array($destTexture)) {
                $canOverwrite = true;
                foreach ($destTexture as $dt) {
                    if (!self::canOverwrite($dt, $noOverwrite)) {
                        $canOverwrite = false;
                        break;
                    }
                }

                if ($canOverwrite) $dst[$name] = $texture;
            }

        }
    }

    private static function canOverwrite(string $texture, array $noOverwrite): bool
    {
        foreach ($noOverwrite as $prefix) {
            if (str_starts_with($texture, $prefix)) return false;
        }

        return true;
    }

    public function getVanillaPath(): ?string
    {
        return $this->path . PocketMap::RESOURCE_PACK_NAME . "/";
    }

    public function getBlockTexturePath(Block $block): ?string
    {
        $textureName = TextureUtils::getBlockTextureName($block);
        if ($block instanceof ExampleBlock) var_dump("Texture name: ".($textureName ?? "<NOT FOUND>"));
        if ($textureName === null) return null;

        $textures = $this->getTextureByName($textureName);

        // the texture is just a straight forward texture path
        if (is_string($textures)) return $this->getRealTexturePath($textures);

        // well done, you found a block without texture
        if (!is_array($textures) || empty($textures)) return null;

        if (array_key_exists("path", $textures)) {
            return $this->getRealTexturePath($textures["path"]);
        }

        // check if the textures has some rotations
        $faceIntersect = array_intersect(array_keys($textures), ["up", "down", "side", "north", "east", "south", "west"]);
        if (!empty($faceIntersect)) {
            // TODO: check for block rotations in the world
            $textures = $textures["up"] ?? $textures[array_key_first($textures)];

            // it's a single textures
            if (is_string($textures)) return $this->getRealTexturePath($textures);
        }

        // texture contains image path and tint_color
        if (isset($textures[0]) && is_array($textures[0])) {
            // it doesn't have a path for some reason
            if (!array_key_exists("path", $textures[0])) return null;
            // return the path
            return $this->getRealTexturePath($textures[0]["path"]);
        }

        // only a single entry
        if (count($textures) == 1) return $this->getRealTexturePath($textures[0]);

        // get the data value of the block
        // the data value determines which texture to use of a list of textures
        $dataValue = BlockDataValues::getDataValue($block);

        // unknown data value
        if (!$textures[$dataValue]) return null;

        if (is_array($textures[$dataValue])) return $this->getRealTexturePath($textures[$dataValue]["path"]);
        return $this->getRealTexturePath($textures[$dataValue]);
    }

    public function getTextureByName(string $name): null|string|array
    {
        return $this->getBlocks()[$name] ?? null;
    }

    /**
     * Get all blocks in the block index
     * @return array
     */
    public function getBlocks(): array
    {
        return $this->getTerrainTextures()["blocks"] ?? [];
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