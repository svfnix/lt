<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;

class debug extends Command
{
    protected $signature = 'tg:debug';
    protected $description = 'Debug tool';

    public function handle()
    {
        $telegram = new Api();
        $response = $telegram->getUpdates([
            'offset' => '1',
            'limit'=>1]
        );

        print_r($response);
    }
}
