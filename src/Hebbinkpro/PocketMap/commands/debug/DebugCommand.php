<?php

namespace Hebbinkpro\PocketMap\commands\debug;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class DebugCommand extends BaseSubCommand
{

    protected function prepare(): void
    {
        $this->setPermission("pocketmap.cmd.debug");
        /** @var PluginBase $plugin */
        $plugin = $this->getOwningPlugin();

        $this->registerSubCommand(new DebugBlocksCommand($plugin, "blocks", "Loads a grid with all the registered blocks on your position"));
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array<mixed> $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage("§e[WARNING] Executing debug commands can cause temporary lag on your server!");
    }
}