<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class hook extends Command
{
    protected $signature = 'tg:hook';
    protected $description = 'Set webhook';

    public function handle()
    {
        $telegram = new Api();
        $response = $telegram->setWebhook([
            'url' => env('DOMAIN') . '/bot/webhook',
            'certificate' => 'storage/certificates/certificate.pub'
        ]);

        $this->info(print_r($response, 1));

    }
}
