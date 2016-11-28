<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;

class hookoff extends Command
{
    protected $signature = 'tg:hookoff';
    protected $description = 'Set webhook off';

    /**
     *
     */
    public function handle()
    {
        $telegram = new Api();
        $response = $telegram->removeWebhook();
        $this->info(print_r($response, 1));

    }
}
