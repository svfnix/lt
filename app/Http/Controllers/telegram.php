<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Telegram\Bot\Api;

class telegram extends Controller
{
    /**
     * @var Api()
     */
    private $telegram;
    private $type;
    private $file;

    private $chatid;
    private $channel;

    function clear(){
        cache(['state' => null], Carbon::now()->addSecond());
        cache(['type' => null], Carbon::now()->addSecond());
        cache(['file' => null], Carbon::now()->addSecond());
        cache(['caption' => null], Carbon::now()->addSecond());
    }

    function setState($state){
        cache(['state' => $state], Carbon::now()->addYears(1));
    }

    function getState(){
        return cache('state', null);
    }

    function saveRequest($type, $file){
        cache(['type' => $type], Carbon::now()->addYears(1));
        cache(['file' => $file], Carbon::now()->addYears(1));
    }

    function loadRequest(){
        $this->type = cache('type');
        $this->file = cache('file');
    }

    function setCaption($caption){
        cache(['caption' => $caption], Carbon::now()->addYears(1));
    }

    function getCaption(){
        return cache('caption', null);
    }

    function msg($message){
        if(is_array($message)){
            $message = print_r($message, 1);
        }

        $chats = explode(',', $this->chatid);
        foreach ($chats as $chat) {
            $this->telegram->sendMessage([
                'chat_id' => $chat,
                'text' => $message
            ]);
        }
    }

    function getFileUrl($file){
        $file  = $this->telegram->getFile(['file_id' => $file]);
        return 'https://api.telegram.org/file/bot'.config('telegram.bot_token').'/'.$file->getFilePath();
    }

    function generateCaption($message){
        $text = $message->has('text') ? $message->getText() : '';
        return implode("\n\n", [$text, '💟 @telegfa']);
    }

    public function handle()
    {
        $this->chatid = env('TELEGRAM_CHAT_ID');
        $this->channel = env('TELEGRAM_CHANNEL');

        $msg_accept = 'بله ارسال کن';
        $msg_edit = 'ویرایش پیام';
        $msg_reject = 'انصراف و حذف پیام';

        $this->telegram = new Api();
        $response = $this->telegram->getWebhookUpdates();
        $message = $response->getMessage();

        if($message->has('photo')){

            $this->clear();
            $photo = $message->getPhoto();
            $this->saveRequest('photo', $photo[count($photo)-1]['file_id']);

            $this->msg('Please send caption:');
            $this->setState('STATE_SEND_CAPTION');

        } elseif($message->has('video')) {

            $this->clear();
            $this->saveRequest('video', $message->getVideo()->getFileId());

            $this->msg('Please send caption:');
            $this->setState('STATE_SEND_CAPTION');

        }  elseif($message->has('document')) {

            $this->clear();
            $document = $message->getDocument();
            switch ($document->getMimeType()){
                case 'video/mp4':

                    $this->saveRequest('video', $this->getFileUrl($document->getFileId()));
                    $this->msg('Please send caption:');
                    $this->setState('STATE_SEND_CAPTION');

                    break;
            }

        } else {

            $this->loadRequest();

            switch ($this->getState()){

                case 'STATE_SEND_CAPTION':
                    $keyboard = [
                        [$msg_accept],
                        [$msg_edit],
                        [$msg_reject]
                    ];

                    $reply_markup = $this->telegram->replyKeyboardMarkup([
                        'keyboard' => $keyboard,
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true
                    ]);

                    $caption = $this->generateCaption($message);
                    $this->setCaption($caption);

                    $func = 'send' . ucfirst($this->type);

                    $chats = explode(',', $this->chatid);
                    foreach ($chats as $chat) {
                        $this->telegram->$func([
                            'chat_id' => $chat,
                            $this->type => $this->file,
                            'caption' => $caption,
                            'reply_markup' => $reply_markup
                        ]);
                    }

                    $this->msg('Are you sure want to send this message to channel?');
                    $this->setState('STATE_GET_CONFIRM');
                    break;

                case 'STATE_GET_CONFIRM':

                    $text = $message->has('text') ? $message->getText() : '';

                    switch ($text){
                        case $msg_accept:

                            if($this->type == 'text'){

                                $this->telegram->sendMessage([
                                    'chat_id' => $this->channel,
                                    'text' => $this->getCaption()
                                ]);

                            }else {

                                $func = 'send' . ucfirst($this->type);
                                $this->telegram->$func([
                                    'chat_id' => $this->channel,
                                    $this->type => $this->file,
                                    'caption' => $this->getCaption()
                                ]);

                            }

                            $this->msg('Message sent successfully.');
                            $this->clear();

                            break;

                        case $msg_edit:

                            $this->setState('STATE_SEND_CAPTION');
                            $this->msg('Please send caption:');

                            break;

                        case $msg_reject:

                            $this->clear();
                            $this->msg('Message rejected!');

                            break;
                    }
                    break;

                default:
                    if($message->has('text')) {

                        $this->clear();
                        $this->saveRequest('text', null);
                        $caption = $this->generateCaption($message->getText());
                        $this->setCaption($caption);
                        $this->msg($caption);
                        $this->msg('Are you sure want to send this message to channel?');
                        $this->setState('STATE_GET_CONFIRM');

                    } else {
                        $this->msg('unknown message');
                    }
                    break;
            }
        }
    }
}
