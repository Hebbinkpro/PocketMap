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

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

/**
 * This base extension can be used to add functionality to PocketMap which requires other plugins to be loaded.
 *
 * @internal Extensions are meant for build-in PocketMap extension and not for use inside other plugins.
 *           If you want to use PocketMap in your own plugin, make use of depend or soft-depend in your plugin.yml.
 */
abstract class BaseExtension
{
    private PluginBase $plugin;
    /** @var array<string, PluginBase> */
    private array $plugins;

    private bool $enabled = false;

    final public function __construct(PluginBase $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Get a list of plugin names required for this extension
     * @return string[]
     */
    abstract public static function getRequiredPlugins(): array;

    /**
     * Get a list of all class names that are required for this extension.
     * @return class-string[]
     */
    public static function getRequiredClasses(): array
    {
        return [];
    }

    /**
     * @param string|null $name the name of the required plugin, no name will return the parent
     * @return PluginBase|null the requested plugin
     */
    public function getPlugin(string $name = null): ?PluginBase
    {
        if ($name === null) return $this->plugin;
        return $this->plugins[$name] ?? null;
    }

    /**
     * Enable the extension
     * @param array<string, PluginBase> $plugins the required plugins
     * @return void
     * @internal This function should only be called by the ExtensionManager
     */
    final public function enable(array $plugins): void
    {
        $this->plugins = $plugins;
        $this->onEnable();
        $this->enabled = true;
    }

    /**
     * Called when the extension is enabled;
     * @return void
     * @internal This function should only be called by the enable function
     */
    abstract protected function onEnable(): void;

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Disable the extension
     * @return void
     */
    final public function disable(): void
    {
        $this->onDisable();
        $this->enabled = false;
    }

    /**
     * Called when the extension is disabled
     * @return void
     */
    protected function onDisable(): void
    {

    }

    /**
     * Wrapper around registering events. The events will be registered with the owning plugin as owner.
     * @param Listener $listener the listener
     * @return void
     */
    protected function registerEvents(Listener $listener): void
    {
        $this->plugin->getServer()->getPluginManager()->registerEvents($listener, $this->plugin);
    }

    public function getServer(): Server
    {
        return $this->plugin->getServer();
    }
}