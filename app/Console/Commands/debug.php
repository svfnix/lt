<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;

class debug extends Command
{
    protected $signature = 'tg:debug';
    protected $description = 'Debug tool';

    function file_url($file){
        return 'https://api.telegram.org/file/bot'.config('telegram.bot_token').'/'.$file;
    }

    function addSignature($message){
        return "{$message}\n\n💟 @telegfa";
    }

    public function handle()
    {
        $telegram = new Api();
        $response = $telegram->getUpdates();

        print_r($response);
    }
}
