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

namespace Hebbinkpro\PocketMap\commands\marker;

use CortexPE\Commando\args\StringEnumArgument;
use Hebbinkpro\PocketMap\api\MarkerManager;
use pocketmine\command\CommandSender;

class MarkerIconArgument extends StringEnumArgument
{
    private MarkerManager $markers;

    public function __construct(string $name, MarkerManager $markers, bool $optional = false)
    {
        $this->markers = $markers;
        parent::__construct($name, $optional);
    }

    public function parse(string $argument, CommandSender $sender): string
    {
        return $argument;
    }

    public function getTypeName(): string
    {
        return "enum";
    }

    public function getEnumName(): string
    {
        return "icon name";
    }

    /**
     * @return array<int|string, string>
     */
    public function getEnumValues(): array
    {
        $values = [];
        foreach ($this->markers->getIcons() as $icon) {
            $values[$icon] = $icon;
        }
        return $values;
    }
}