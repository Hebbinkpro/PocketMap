<?php

namespace Hebbinkpro\PocketMap\task;

use pocketmine\scheduler\AsyncTask;

class AsyncUpdateRegionRenderTask extends AsyncTask
{

    // TODO: Create a region renderer that only renders a given part of a region.
    // e.g. only an area of 16x16 chunks in an 256x256 chunk render

    public function onRun(): void
    {
        // TODO: generate image of the given area and write it to the existing image
    }

    public function onCompletion(): void
    {
        // TODO: tell the scheduler that the render of the area is completed.
        // This is needed because we cannot have multiple threads editing the same image at the same time
    }
}