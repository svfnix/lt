<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;

class test extends Command
{

    const CHATID = '294712249';

    protected $signature = 'tg:test';
    protected $description = 'Command description';

    public function handle()
    {
        
        $telegram = new Api();

        $response = $telegram->getUpdates();

        /*foreach ($response as $msg) {
            $telegram->sendMessage([
                'chat_id' => $msg->getMessage()->getChat()->getId(),
                'text' => $msg->getMessage()->getChat()->getId()
            ]);
            $response = $telegram->sendVideo([
                'chat_id' => $msg->getMessage()->getChat()->getId(),
                'video' => '/home/svf/video.mp4',
                'caption' => 'Hello World @svfnix'
            ]);
        }*/

        $msg = array_pop($response);
        /*$telegram->sendVideo([
            'chat_id' => self::CHATID,
            'video' =>  $msg->getMessage()->getDocument()->getFileId() ,//'/home/svf/video.mp4',
            'caption' => 'Hello World @svfnix'
        ]);*/

        $keyboard = [
            ['بله'],
            ['خیر']
        ];

        $reply_markup = $telegram->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $telegram->sendMessage([
            'chat_id' => self::CHATID,
            'text' => 'salam',
            'reply_markup' => $reply_markup
        ]);

        //$response = $telegram->getFile(['file_id' => $msg->getMessage()->getDocument()->getFileId()]);

        $this->info(json_encode($response, 1));

    }
}
