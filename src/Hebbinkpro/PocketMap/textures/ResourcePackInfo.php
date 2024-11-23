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

class ResourcePackInfo implements JsonSerializable
{
    private string $uuid;
    private string $file;
    private string $version;
    private string $hash;

    public function __construct(string $uuid, string $file, string $version, string $hash)
    {
        $this->uuid = $uuid;
        $this->file = $file;
        $this->version = $version;
        $this->hash = $hash;
    }

    public static function fromArray(array $data): self
    {
        return new self($data["uuid"], $data["file"], $data["version"], $data["hash"]);
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    public function jsonSerialize(): array
    {
        return [
            "uuid" => $this->uuid,
            "file" => $this->file,
            "version" => $this->version,
            "sha256" => $this->hash,
        ];
    }
}