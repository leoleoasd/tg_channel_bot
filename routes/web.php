<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $response = Telegram::getMe();

    $botId = $response->getId();
    $firstName = $response->getFirstName();
    $username = $response->getUsername();
    dd($response);
});

Route::get('/setWebhook', function(){
    dd(Telegram::setWebhook([
        'url' => config('app.url').config("tgbot.webhook_url"),
    ]));
});

Route::get('/getWebhook', function(){
    dd(Telegram::getWebhookInfo(null));
});


Route::any(config('tgbot.webhook_url'),"WebhookController@handle");

use Illuminate\Support\Facades\Redis;

Route::get('/test',function (){
    /*
    Redis::pipeline(function ($pipe) use(&$message_id,&$chat_id,&$message) {
        $message_id = $pipe->get('last_message_id');
        $chat_id = $pipe->get('last_chat_id');
        $pipe->set('last_message_id',rand());
        $pipe->set('last_chat_id',rand());
    });
    */
    // eval "return redis.call('set','telegram',123,'ex',10,'nx')" 0
});
