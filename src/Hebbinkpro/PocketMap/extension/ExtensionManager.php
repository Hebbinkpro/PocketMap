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

namespace Hebbinkpro\PocketMap\extension;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class ExtensionManager
{
    use SingletonTrait;

    /** @var array<string, BaseExtension> */
    private array $extensions = [];

    /**
     * Register a new extension
     * @param PluginBase $plugin the owning plugin of the extension
     * @param string $name the name of the extension
     * @param class-string<BaseExtension> $classname the extension class
     * @return bool if the extension is registered
     */
    public function registerExtension(PluginBase $plugin, string $name, string $classname): bool
    {
        if (!is_subclass_of($classname, BaseExtension::class) || isset($this->extensions[$name])) return false;

        $extension = new $classname($plugin);
        $this->extensions[$name] = $extension;

        return true;
    }

    /**
     * Enable all the registered extensions
     * @return void
     */
    public function enableAll(): void
    {
        foreach ($this->extensions as $name => $extension) {
            $this->enableExtension($name);
        }
    }

    /**
     * Enable the given extension
     * @param string $name the extension name
     * @return bool if the extension is enabled
     */
    public function enableExtension(string $name): bool
    {
        // get the extension
        $extension = $this->extensions[$name] ?? null;
        if ($extension === null) return false;


        // get all required plugins
        $plugins = [];
        foreach ($extension::getRequiredPlugins() as $pluginName) {
            $plugin = Server::getInstance()->getPluginManager()->getPlugin($pluginName);
            if ($plugin === null) return false;
            $plugins[$pluginName] = $plugin;
        }

        // check if all required classes are present
        foreach ($extension::getRequiredClasses() as $classname) {
            if (!class_exists($classname)) return false;
        }


        // enable the extension and give the required plugins
        $extension->enable($plugins);
        return true;
    }


}