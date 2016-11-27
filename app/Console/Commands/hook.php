<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;

class hook extends Command
{
    protected $signature = 'tg:hook';
    protected $description = 'Set webhook';

    public function handle()
    {
        $telegram = new Api();
        $response = $telegram->setWebhook([
            'url' => 'http://' . env('DOMAIN') . '/bot/webhook',
            'certificate' => 'storage/certificates/ssl.pem'
        ]);

        $this->info(print_r($response, 1));

    }
}
