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

namespace Hebbinkpro\PocketMap\textures;

use JsonSerializable;

/**
 * Class containing all resource packs information required for the terrain textures
 */
class ResourcePacksInfo implements JsonSerializable
{

    private string $vanilla;
    /** @var array<string, ResourcePackInfo> */
    private array $resourcePacks;

    /**
     * @param string $vanilla
     * @param array<string, ResourcePackInfo> $resourcePacks
     */
    public function __construct(string $vanilla = "", array $resourcePacks = [])
    {
        $this->vanilla = $vanilla;
        $this->resourcePacks = $resourcePacks;
    }

    public static function fromArray(array $data): self
    {
        $packs = array_map(fn(array $pack) => ResourcePackInfo::fromArray($pack), $data["resource_packs"] ?? []);
        return new self($data["vanilla"], $packs);
    }

    /**
     * @return array<string, ResourcePackInfo>
     */
    public function getResourcePacks(): array
    {
        return $this->resourcePacks;
    }

    /**
     * Compare the serialized terrain texture info instances
     * @param ResourcePacksInfo $terrainInfo
     * @return bool if the serialized instances are equal
     */
    public function equals(ResourcePacksInfo $terrainInfo): bool
    {
        return $this->jsonSerialize() === $terrainInfo->jsonSerialize();
    }

    public function jsonSerialize(): array
    {
        return [
            "vanilla" => $this->vanilla,
            "resource_packs" => array_map(fn($pack) => $pack->jsonSerialize(), $this->resourcePacks),
        ];
    }
}