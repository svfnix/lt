<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;

class manual extends Command
{
    protected $signature = 'tg:manual';
    protected $description = 'Get message manual';

    public function handle()
    {
        $telegram = new Api();
        $response = $telegram->getUpdates();
        $this->info(print_r($response, 1));

    }
}
