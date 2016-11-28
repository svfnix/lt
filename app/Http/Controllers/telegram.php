<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class telegram extends Controller
{

    function file_url($file){
        return 'https://api.telegram.org/file/bot'.config('telegram.bot_token').'/'.$file;
    }

    function addSignature($message){
        return "{$message}\n\n💟 @telegfa";
    }

    public function handle()
    {
        $CHAT_ID = env('TELEGRAM_CHAT_ID');
        $CHANNEL = env('TELEGRAM_CHANNEL');

        $msg_accept = 'بله ارسال کن';
        $msg_edit = 'ویرایش پیام';
        $msg_reject = 'انصراف و حذف پیام';


        $telegram = new Api();
        $response = $telegram->getWebhookUpdates();
        $telegram->sendMessage([
            'chat_id' => $CHAT_ID,
            'text' => print_r($response, 1)
        ]);
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

                cache(['state' => 'STATE_SEND_CAPTION'], Carbon::now()->addMinutes(10));
                cache(['type' => $type], Carbon::now()->addMinutes(10));
                cache(['file' => $file], Carbon::now()->addMinutes(10));

                $telegram->sendMessage([
                    'chat_id' => $CHAT_ID,
                    'text' => 'Please send caption:'
                ]);

                break;

            default:

                switch (Cache::get('state')){
                    case 'STATE_SEND_CAPTION':

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
                            'chat_id' => $CHAT_ID,
                            $type => cache('file'),
                            'caption' => $caption,
                            'reply_markup' => $reply_markup
                        ]);

                        $telegram->sendMessage([
                            'chat_id' => $CHAT_ID,
                            'text' => 'Are you sure want to send this message to channel?'
                        ]);

                        cache(['state' => 'STATE_GET_CONFIRM'], Carbon::now()->addMinutes(10));

                        break;


                    case 'STATE_GET_CONFIRM':
                        $choose = $msg->getMessage()->getText();

                        switch ($choose){
                            case $msg_accept:

                                $type = cache('type');
                                $func = 'send' . ucfirst($type);

                                $telegram->$func([
                                    'chat_id' => $CHAT_ID,
                                    $type => cache('file'),
                                    'caption' =>  cache('caption')
                                ]);

                                $telegram->sendMessage([
                                    'chat_id' => $CHAT_ID,
                                    'text' => 'Message sent successfully.'
                                ]);

                                cache(['state' => null], Carbon::now()->addSecond());
                                cache(['type' => null], Carbon::now()->addSecond());
                                cache(['file' => null], Carbon::now()->addSecond());
                                cache(['caption' => null], Carbon::now()->addSecond());

                                break;

                            case $msg_edit:

                                cache(['state' => 'STATE_SEND_CAPTION'], Carbon::now()->addMinutes(10));
                                $telegram->sendMessage([
                                    'chat_id' => $CHAT_ID,
                                    'text' => 'Please send caption:'
                                ]);

                                break;

                            case $msg_reject:

                                cache(['state' => null], Carbon::now()->addSecond());
                                cache(['type' => null], Carbon::now()->addSecond());
                                cache(['file' => null], Carbon::now()->addSecond());
                                cache(['caption' => null], Carbon::now()->addSecond());

                                $telegram->sendMessage([
                                    'chat_id' => $CHAT_ID,
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
