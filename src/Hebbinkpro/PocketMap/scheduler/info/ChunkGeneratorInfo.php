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

namespace Hebbinkpro\PocketMap\scheduler\info;

use Generator;
use Hebbinkpro\PocketMap\render\WorldRenderer;

class ChunkGeneratorInfo
{
    private WorldRenderer $renderer;
    /** @var Generator<array{int,int}> */
    private Generator $chunks;
    private ChunkGeneratorType $type;
    private int $count;

    /**
     * @param WorldRenderer $renderer
     * @param Generator<array{int,int}> $chunks
     * @param ChunkGeneratorType $generatorType
     * @param int $count
     */
    public function __construct(WorldRenderer $renderer, Generator $chunks, ChunkGeneratorType $generatorType, int $count)
    {
        $this->renderer = $renderer;
        $this->chunks = $chunks;
        $this->type = $generatorType;
        $this->count = $count;
    }

    /**
     * @return WorldRenderer
     */
    public function getRenderer(): WorldRenderer
    {
        return $this->renderer;
    }

    /**
     * @return Generator<array{int,int}>
     */
    public function getChunks(): Generator
    {
        return $this->chunks;
    }

    /**
     * @return ChunkGeneratorType
     */
    public function getType(): ChunkGeneratorType
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Increment the count
     * @return void
     */
    public function incrementCount(): void
    {
        $this->count++;
    }

}