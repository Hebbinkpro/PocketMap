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
 * Copyright (C) 2023 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\PocketMap\utils;

use Exception;
use JsonException;
use pocketmine\utils\Config;

class ConfigManager
{
    private Config|ConfigManager $config;
    private bool $autoSave;
    /** @var array<mixed> */
    private array $configData;
    private ?string $name;
    /** @var array<string, ConfigManager> */
    private array $managers;


    /**
     * @param Config|ConfigManager $config
     * @param bool $autoSave
     * @param array<string, mixed> $configData
     * @param string|null $name
     */
    private function __construct(Config|ConfigManager $config, bool $autoSave = false, array $configData = [], ?string $name = null)
    {
        if ($config instanceof Config) $configData = $config->getAll();

        $this->config = $config;
        $this->autoSave = $autoSave;
        $this->configData = $configData;
        $this->name = $name;
        $this->managers = [];
    }

    /**
     * Create a new ConfigManager from a Config file
     * @param Config $config the config
     * @param bool $autoSave if the data should immediately be saved to the config file
     * @return ConfigManager the config manager
     */
    public static function fromConfig(Config $config, bool $autoSave = false): ConfigManager
    {
        return new ConfigManager($config, $autoSave);
    }

    /**
     * Create a new ConfigManager from a Config file
     * @param string $file the config file
     * @param bool $autoSave if the data should immediately be saved to the config file
     * @return ConfigManager the config manager
     */
    public static function fromFile(string $file, bool $autoSave = false): ConfigManager
    {
        return new ConfigManager(new Config($file), $autoSave);
    }

    /**
     * @throws JsonException
     */
    public function save(): void
    {
        if ($this->config instanceof Config) {
            // set all values to the array of our configData
            $this->config->setAll($this->toArray());
            //save the config
            $this->config->save();
            return;
        }

        // the config is a config manager
        // setValue will update all managers above this one
        if ($this->name !== null) $this->config->setValue($this->name, $this->toArray());
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->configData;
    }

    /**
     * Set a value in the config
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws JsonException
     */
    public function setValue(string $name, mixed $value): void
    {
        $nameParts = explode(".", $name);
        // when size is 0, we found the correct one
        if (count($nameParts) == 1) {
            $this->configData[$nameParts[0]] = $value;
            if ($this->autoSave) $this->save();
            return;
        }

        $manager = $this->getManager(array_shift($nameParts), true);

        if ($manager !== null) {
            $manager->setValue(implode(".", $nameParts), $value);
            $manager->save();
        }
    }

    /**
     * Get a config manager from the config
     * @param string $name the name of the config inside the config
     * @param bool $create if the manager should be created if it doesn't exist
     * @param array<mixed> $default the default values when a new manager is created
     * @return ConfigManager|null the config manager or null when it doesn't exist.
     */
    public function getManager(string $name, bool $create = false, array $default = []): ?ConfigManager
    {
        if (array_key_exists($name, $this->managers)) return $this->managers[$name];

        $nameParts = explode(".", $name);

        // get the value
        if (count($nameParts) == 1) {
            $value = $this->getValue($nameParts[0]);
            if (!is_array($value)) {
                // there is already another value in there
                if ($value !== null || !$create) return null;
                // set the value to an empty array
                $value = $default;
            }

            $mngr = new ConfigManager($this, true, $value, $nameParts[0]);
            try {
                $mngr->save();
            } catch (JsonException) {
                // something went wrong while saving
                return null;
            }
            return $mngr;
        }

        // find the manager
        $mngr = $this->getManager(array_shift($nameParts), $create, $default);
        // the manager does not exist, return null
        if ($mngr === null) return null;

        // find the value inside this manager
        return $mngr->getManager(implode(".", $nameParts), $create, $default);
    }

    /**
     * Get a value from the config
     * @param string $name the name of the value
     * @param mixed $default the default value when the value does not exist
     * @return mixed the value or the default value
     */
    public function getValue(string $name, mixed $default = null): mixed
    {

        $nameParts = explode(".", $name);

        // get the value
        if (count($nameParts) == 1) {
            return $this->configData[$nameParts[0]] ?? $default;
        }

        try {
            // find the manager
            $mngr = $this->getManager(array_shift($nameParts));
            // the manager does not exist, return null
            if ($mngr === null) return $default;

            // find the value inside this manager
            return $mngr->getValue(implode(".", $nameParts));
        } catch (Exception) {
            return $default;
        }
    }

    /**
     * Get an integer value from the config
     * @param string $name
     * @param int $default
     * @return int
     */
    public function getInt(string $name, int $default = 0): int
    {
        $res = $this->getValue($name, $default);
        return is_numeric($res) ? (int)$res : $default;
    }

    /**
     * Get a boolean value from the config
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public function getBool(string $name, bool $default = false): bool
    {
        return boolval($this->getValue($name, $default));
    }

    /**
     * Get a float value from the config
     * @param string $name
     * @param float $default
     * @return float
     */
    public function getFloat(string $name, float $default = 0.0): float
    {
        $res = $this->getValue($name, $default);
        return is_numeric($res) ? (float)$res : $default;
    }

    /**
     * Get an array value from the config
     * @param string $name
     * @param array<mixed> $default
     * @return array<mixed>
     */
    public function getArray(string $name, array $default = []): array
    {
        $value = $this->getValue($name, $default);
        if (!is_array($value)) return $default;
        return $value;
    }

    /**
     * Get a json encoded value from the config.
     * The value will be decoded using 'json_decode(value, $associative)'.
     * @param string $name
     * @param bool $associative
     * @return mixed
     */
    public function getJsonEncodedValue(string $name, bool $associative = true): mixed
    {
        return json_decode($this->getString($name), $associative);
    }

    /**
     * Get a string value
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getString(string $name, string $default = ""): string
    {
        $res = $this->getValue($name, $default);
        return is_string($res) ? $res : $default;
    }

    /**
     * Get a serialized value from the config.
     * The value will be unserialized using 'unserialize(value)'.
     * @param string $name
     * @return mixed
     */
    public function getSerializedValue(string $name): mixed
    {
        return unserialize($this->getString($name));
    }


}