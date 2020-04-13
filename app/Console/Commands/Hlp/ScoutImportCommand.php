<?php

namespace App\Console\Commands\Hlp;

use Laravel\Scout\Console\ImportCommand;

class ScoutImportCommand extends ImportCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hlp:scout-import {model}';
}
