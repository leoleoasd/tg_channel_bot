<?php

namespace App\Http\Controllers;

use App\Forward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram;
use Telegram\Bot\Objects\Message;

class WebhookController extends Controller
{
    public function handle(Request $r){
        $update = Telegram::getWebhookUpdate();
        if($update->getMessage() and $update->getMessage()->getChat()->getType()=='private')
            Telegram::commandsHandler(true);
        Log::info(json_encode($update));
        $message = $update->getMessage();
        if($message){
            // Check if admins had changed their name.
            if(in_array($message->getFrom()->getId(), config('tgbot.admins'))){
                $name = "";
                foreach(config('tgbot.admins') as $adminName => $admin){
                    if($admin == $message->getFrom()->getId()){
                        $name = $adminName;
                        break;
                    }
                }
                $nowName = $message->getFrom()->getFirstName() . " " . $message->getFrom()->getLastName();
                if($message->getFrom()->getLastName() == null){
                    $nowName = $message->getFrom()->getFirstName();
                }
                if($nowName != $name){
                    Telegram::sendMessage([
                        'chat_id' => $message->getFrom()->getId(),
                        'text' => "You have changed your name. Please update it in the bot config to insure bot can work properly.",
                    ]);
                }
            }
            if(in_array($message->getChat()->getId(), config("tgbot.channels"))){
                if($message->getPinnedMessage()){
                    if($message->getPinnedMessage()->getFrom()->getId() == config("tgbot.self")){
                        Telegram::deleteMessage([
                            'chat_id' => $message->getChat()->getId(),
                            'message_id' => $message->getMessageId(),
                        ]);
                    }
                }
                if($message->getText() == "@admins" or $message->getText() == "@admin"){
                    $text = "";
                    foreach(config("tgbot.groups")[$message->getChat()->getId()] as $admin){
                        $text.="<a href=\"tg://user?id=$admin\">@".config("tgbot.admin_nickname")[$admin]."</a> ";
                    }
                    $args = [
                        'chat_id' => $message->getChat()->getId(),
                        'text' => $text,
                        'parse_mode' => "HTML",
                        'reply_to_message_id' => $message->getMessageId(),
                    ];
                    $chatMessage = Telegram::sendMessage($args);
                }
                if($message->getReplyToMessage()){
                    if($message->getReplyToMessage()->getFrom()->getId() == config("tgbot.self")){
                        $forward = Forward::where("to_chat_id",$message->getReplyToMessage()->getChat()->getId())->where("to_message_id",$message->getReplyToMessage()->getMessageId())->first();
                        if($forward and $forward->from_user_id != $message->getFrom()->getId()){
                            $args = [
                                'chat_id' => $message->getChat()->getId(),
                                'text' => format(config("tgbot.templates.reply"),[
                                    'user' => config("tgbot.admin_nickname")[$forward->from_user_id],
                                    'userId' => $forward->from_user_id,
                                ]),
                                'parse_mode' => "HTML",
                                'reply_to_message_id' => $message->getMessageId(),
                            ];
                            $chatMessage = Telegram::sendMessage($args);
                        }
                    }
                }
            }
        }
        $channelUpdate = $update->get('channel_post');
        if($channelUpdate){
            // Forward messages to linked groups.
            $message = new Message($channelUpdate);
            $channelId = $message->getChat()->getId();
            if(array_key_exists($channelId, config('tgbot.channels'))){
                $global_args = [];
                if($message->getReplyToMessage()){
                    $reply_to_message = $message->getReplyToMessage();
                    $forward = Forward::where('from_message_id', $reply_to_message->getMessageId())->where('from_chat_id', $reply_to_message->getChat()->getId())->first();
                    if($forward){
                        $global_args['reply_to_message_id'] = $forward->to_message_id;
                    }
                }
                $entities = $message->getEntities() ?? $message->getCaptionEntities();
                if($entities){
                    $text = $message->getText() ?? $message->getCaption();
                    foreach ($entities as $entity){
                        if($entity['type'] == 'hashtag'){
                            if(substr($text,$entity['offset'],$entity['length']) == "#noforward"){
                                return 'ok';
                            }
                        }
                    }
                }
                if($message->isType('text')){
                    try {
                        $args = array_merge([
                            'chat_id' => config('tgbot.channels')[$channelId],
                            'text' => format(config('tgbot.templates.text'), [
                                'user' => config("tgbot.admin_nickname")[config("tgbot.admins")[$message->get('author_signature')] ?? null] ?? null,
                                'userId' => config('tgbot.admins')[$message->get('author_signature')] ?? null,
                                'channel' => $message->getChat()->getUsername() ? "@" . $message->getChat()->getUsername() : $message->getChat()->getTitle(),
                                'text' => htmlspecialchars($message->getText()),
                            ]),
                            'parse_mode' => "HTML"
                        ], $global_args);
                        $chatMessage = Telegram::sendMessage($args);
                    } catch (Telegram\Bot\Exceptions\TelegramSDKException $e) {
                        \Log::error($e);
                        // TODO: handle exceptions.
                    }
                    if(config('tgbot.pin')){
                        Telegram::pinChatMessage([
                            'chat_id' => config('tgbot.channels')[$channelId],
                            'message_id' => $chatMessage->getMessageId(),
                            'disable_notification' => True,
                        ]);
                    }
                    $forward = new Forward();
                    $forward->from_chat_id = $message->getChat()->getId();
                    $forward->from_message_id = $message->getMessageId();
                    $forward->from_user_id = config('tgbot.admins')[$message->get('author_signature')] ?? 0;
                    $forward->to_chat_id = $chatMessage->getChat()->getId();
                    $forward->to_message_id = $chatMessage->getMessageId();
                    $forward->save();
                }else if($message->isType('photo')){
                    try {
                        $args = array_merge([
                            'chat_id' => config('tgbot.channels')[$channelId],
                            'caption' => format(config('tgbot.templates.text'), [
                                'user' => config("tgbot.admin_nickname")[config("tgbot.admins")[$message->get('author_signature')] ?? null] ?? null,
                                'userId' => config('tgbot.admins')[$message->get('author_signature')] ?? null,
                                'channel' => $message->getChat()->getUsername() ? "@" . $message->getChat()->getUsername() : $message->getChat()->getTitle(),
                                'text' => htmlspecialchars($message->getCaption()),
                            ]),
                            'photo' => $message->getRawResponse()['photo'][0]['file_id'],
                            'parse_mode' => "HTML"
                        ],$global_args);
                        $chatMessage = Telegram::sendPhoto($args);
                    } catch (Telegram\Bot\Exceptions\TelegramSDKException $e) {
                        \Log::error($e);
                        // TODO: handle exceptions.
                    }
                    if(config('tgbot.pin')){
                        Telegram::pinChatMessage([
                            'chat_id' => config('tgbot.channels')[$channelId],
                            'message_id' => $chatMessage->getMessageId(),
                            'disable_notification' => True,
                        ]);
                    }
                    $forward = new Forward();
                    $forward->from_chat_id = $message->getChat()->getId();
                    $forward->from_message_id = $message->getMessageId();
                    $forward->from_user_id = config('tgbot.admins')[$message->get('author_signature')] ?? 0;
                    $forward->to_chat_id = $chatMessage->getChat()->getId();
                    $forward->to_message_id = $chatMessage->getMessageId();
                    $forward->save();
                }else if($message->isType('video')){
                    try {
                        $args = array_merge([
                            'chat_id' => config('tgbot.channels')[$channelId],
                            'caption' => format(config('tgbot.templates.text'), [
                                'user' => config("tgbot.admin_nickname")[config("tgbot.admins")[$message->get('author_signature')] ?? null] ?? null,
                                'userId' => config('tgbot.admins')[$message->get('author_signature')] ?? null,
                                'channel' => $message->getChat()->getUsername() ? "@" . $message->getChat()->getUsername() : $message->getChat()->getTitle(),
                                'text' => htmlspecialchars($message->getCaption()),
                            ]),
                            'video' => $message->getRawResponse()['video']['file_id'],
                            'parse_mode' => "HTML"
                        ],$global_args);
                        $chatMessage = Telegram::sendVideo($args);
                    } catch (Telegram\Bot\Exceptions\TelegramSDKException $e) {
                        \Log::error($e);
                        // TODO: handle exceptions.
                    }
                    if(config('tgbot.pin')){
                        Telegram::pinChatMessage([
                            'chat_id' => config('tgbot.channels')[$channelId],
                            'message_id' => $chatMessage->getMessageId(),
                            'disable_notification' => True,
                        ]);
                    }
                    $forward = new Forward();
                    $forward->from_chat_id = $message->getChat()->getId();
                    $forward->from_message_id = $message->getMessageId();
                    $forward->from_user_id = config('tgbot.admins')[$message->get('author_signature')] ?? 0;
                    $forward->to_chat_id = $chatMessage->getChat()->getId();
                    $forward->to_message_id = $chatMessage->getMessageId();
                    $forward->save();
                }else{
                    try{
                        $args = array_merge([
                            'chat_id' => config('tgbot.channels')[$channelId],
                            'text' => format(config('tgbot.templates.text'), [
                                'user' => config("tgbot.admin_nickname")[config("tgbot.admins")[$message->get('author_signature')] ?? null] ?? null,
                                'userId' => config('tgbot.admins')[$message->get('author_signature')] ?? null,
                                'channel' => $message->getChat()->getUsername() ? "@" . $message->getChat()->getUsername() : $message->getChat()->getTitle(),
                                'text' => "",
                            ]),
                            'parse_mode' => "HTML"
                        ], $global_args);
                        $chatMessage = Telegram::sendMessage($args);
                        $args = array_merge([
                            'chat_id' => config('tgbot.channels')[$channelId],
                            'from_chat_id' => $message->getChat()->getId(),
                            'message_id' => $message->getMessageId(),
                        ], $global_args);
                        $chatMessage = Telegram::forwardMessage($args);
                        if(config('tgbot.pin')){
                            Telegram::pinChatMessage([
                                'chat_id' => config('tgbot.channels')[$channelId],
                                'message_id' => $chatMessage->getMessageId(),
                                'disable_notification' => True,
                            ]);
                        }
                        $forward = new Forward();
                        $forward->from_chat_id = $message->getChat()->getId();
                        $forward->from_message_id = $message->getMessageId();
                        $forward->from_user_id = config('tgbot.admins')[$message->get('author_signature')] ?? 0;
                        $forward->to_chat_id = $chatMessage->getChat()->getId();
                        $forward->to_message_id = $chatMessage->getMessageId();
                        $forward->save();
                    } catch (Telegram\Bot\Exceptions\TelegramSDKException $e) {
                        \Log::error($e);
                        // TODO: handle exceptions.
                    }
                }
            }
        }
        $editPost = $update->get('edited_channel_post');
        if($editPost){
            $editPost = new Message($editPost);
            if($editPost->isType('text')){
                $forward = Forward::where('from_message_id', $editPost->getMessageId())->where('from_chat_id', $editPost->getChat()->getId())->first();
                if($forward){
                    try {
                        $editMessage = Telegram::editMessageText([
                            'chat_id' => $forward->to_chat_id,
                            'message_id' => $forward->to_message_id,
                            'text' => format(config('tgbot.templates.text'), [
                                'user' => config("tgbot.admin_nickname")[config("tgbot.admins")[$editPost->get('author_signature')] ?? null] ?? null,
                                'userId' => config('tgbot.admins')[$editPost->get('author_signature')] ?? null,
                                'channel' => $editPost->getChat()->getUsername() ? "@" . $editPost->getChat()->getUsername() : $message->getChat()->getTitle(),
                                'text' => htmlspecialchars($editPost->getText()),
                            ]),
                            'parse_mode' => "HTML"
                        ]);
                    } catch (Telegram\Bot\Exceptions\TelegramSDKException $e) {
                        \Log::error($e);
                        // TODO: handle exceptions.
                    }
                }
            }else if($editPost->isType('photo') or $editPost->isType('video')){
                $forward = Forward::where('from_message_id', $editPost->getMessageId())->where('from_chat_id', $editPost->getChat()->getId())->first();
                if($forward){
                    try {
                        $editMessage = Telegram::editMessageCaption([
                            'chat_id' => $forward->to_chat_id,
                            'message_id' => $forward->to_message_id,
                            'caption' => format(config('tgbot.templates.text'), [
                                'user' => config("tgbot.admin_nickname")[config("tgbot.admins")[$editPost->get('author_signature')] ?? null] ?? null,
                                'userId' => config('tgbot.admins')[$editPost->get('author_signature')] ?? null,
                                'channel' => $editPost->getChat()->getUsername() ? "@" . $editPost->getChat()->getUsername() : $message->getChat()->getTitle(),
                                'text' => htmlspecialchars($editPost->getCaption()),
                            ]),
                            'parse_mode' => "HTML"
                        ]);
                    } catch (Telegram\Bot\Exceptions\TelegramSDKException $e) {
                        \Log::error($e);
                        // TODO: handle exceptions.
                    }
                }
            }
        }

        return 'ok';
    }
}
