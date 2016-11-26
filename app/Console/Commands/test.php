<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class test extends Command
{

    const CHATID = '294712249';
    const CHANNEL = '@telegfa';
    const STATE_SEND_CAPTION = 'STATE_SEND_CAPTION';
    const STATE_GET_CONFIRM = 'STATE_GET_CONFIRM';

    protected $signature = 'tg:test';
    protected $description = 'Command description';

    function file_url($file){
        return 'https://api.telegram.org/file/bot'.config('telegram.bot_token').'/'.$file;
    }

    function addSignature($message){
        return "{$message}\n\n💟 @telegfa";
    }

    public function handle()
    {

        $msg_accept = 'بله ارسال کن';
        $msg_edit = 'ویرایش پیام';
        $msg_reject = 'انصراف و حذف پیام';


        $telegram = new Api();
        $response = $telegram->getUpdates();

        $this->info('check messages');

        /**
         * @var Update $msg
         */
        $msg = array_pop($response);

        $photo = $msg->getMessage()->getPhoto();
        if($photo){
            $type = 'photo';
        } else {

            $video = $msg->getMessage()->getVideo();
            if($video){
                $type = 'video';
            } else {
                $voice = $msg->getMessage()->getVoice();
                if($voice){
                    $type = 'voice';
                } else {
                    $document = $msg->getMessage()->getDocument();
                    if($document){
                        $type = 'document';
                    } else {
                        $type = 'text';
                    }
                }
            }
        }

        $this->info('message type: '.$type);

        switch ($type){
            case 'photo':
            case 'video':
            case 'voice':
            case 'document':

                if($type == 'photo'){

                    $photo = (array)$msg->getMessage()->getPhoto();
                    $photo = array_pop($photo);
                    $photo = array_pop($photo);

                    $file = $telegram->getFile(['file_id' => $photo['file_id']])->getFilePath();
                } else{

                    $func = 'get' . ucfirst($type);
                    $file = $telegram->getFile(['file_id' => $msg->getMessage()->$func()->getFileId()])->getFilePath();
                }

                $file = $this->file_url($file);

                cache(['state' => self::STATE_SEND_CAPTION], Carbon::now()->addMinutes(10));
                cache(['type' => $type], Carbon::now()->addMinutes(10));
                cache(['file' => $file], Carbon::now()->addMinutes(10));

                $telegram->sendMessage([
                    'chat_id' => self::CHATID,
                    'text' => 'Please send caption:'
                ]);

                break;

            default:

                $this->info('state: '. Cache::get('state'));

                switch (Cache::get('state')){
                    case self::STATE_SEND_CAPTION:

                        $keyboard = [
                            [$msg_accept],
                            [$msg_edit],
                            [$msg_reject]
                        ];

                        $reply_markup = $telegram->replyKeyboardMarkup([
                            'keyboard' => $keyboard,
                            'resize_keyboard' => true,
                            'one_time_keyboard' => true
                        ]);

                        $caption = $msg->getMessage()->getText();
                        $caption = $this->addSignature($caption);
                        cache(['caption' => $caption], Carbon::now()->addMinutes(10));

                        $type = Cache::get('type');
                        $func = 'send' . ucfirst($type);

                        $telegram->$func([
                            'chat_id' => self::CHATID,
                            $type => cache('file'),
                            'caption' => $caption,
                            'reply_markup' => $reply_markup
                        ]);

                        $telegram->sendMessage([
                            'chat_id' => self::CHATID,
                            'text' => 'Are you sure want to send this message to channel?'
                        ]);

                        cache(['state' => self::STATE_GET_CONFIRM], Carbon::now()->addMinutes(10));

                        break;


                    case self::STATE_GET_CONFIRM:
                        $choose = $msg->getMessage()->getText();

                        switch ($choose){
                            case $msg_accept:

                                $type = cache('type');
                                $func = 'send' . ucfirst($type);

                                $response = $telegram->$func([
                                    'chat_id' => self::CHATID,
                                    $type => cache('file'),
                                    'caption' =>  cache('caption')
                                ]);

                                $telegram->sendMessage([
                                    'chat_id' => self::CHATID,
                                    'text' => 'Message sent successfully.'
                                ]);

                                cache(['state' => null], Carbon::now()->addSecond());
                                cache(['type' => null], Carbon::now()->addSecond());
                                cache(['file' => null], Carbon::now()->addSecond());
                                cache(['caption' => null], Carbon::now()->addSecond());

                                break;

                            case $msg_edit:

                                cache(['state' => self::STATE_SEND_CAPTION], Carbon::now()->addMinutes(10));
                                $telegram->sendMessage([
                                    'chat_id' => self::CHATID,
                                    'text' => 'Please send caption:'
                                ]);

                                break;

                            case $msg_reject:

                                cache(['state' => null], Carbon::now()->addSecond());
                                cache(['type' => null], Carbon::now()->addSecond());
                                cache(['file' => null], Carbon::now()->addSecond());
                                cache(['caption' => null], Carbon::now()->addSecond());

                                $telegram->sendMessage([
                                    'chat_id' => self::CHATID,
                                    'text' => 'Message rejected!'
                                ]);

                                break;
                        }


                        break;
                }


                break;
        }

    }
}
