<?php


namespace App\Commands;


use Carbon\Carbon;
use Telegram\Bot\Commands\Command;
use App\Pet;
use Telegram\Bot\Laravel\Facades\Telegram;
use Redis;

/**
 * Class HelpCommand.
 */
class PetCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'pet';

    /**
     * @var string Command Description
     */
    protected $description = 'Meow~';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        if($this->update->getMessage()->getFrom()->getId() == env("CAT_ID")){
            $this->replyWithMessage([
                'text' => "StackOverFlow\n死递归了呢TwT",
                'reply_to_message_id' => $this->update->getMessage()->getMessageId()
            ]);
            return;
        }
        $pet = new Pet;
        $pet->user_id = $this->update->getMessage()->getFrom()->getId();
        $pet->chat_id = $this->update->getMessage()->getChat()->getId();
        $pet->is_messaged = 0;
        $pet->message_id = 0;
        $pet->is_sent = 0;
        $pet->save();
        $count = Pet::where('created_at','>',Carbon::now()->startOfDay()->toDateTimeString())
            ->count();
        if($pet->chat_id > 0) {
            $pet->is_messaged = 1;
            $pet->is_sent = 1;
            $pet->save();
            try {
                if ($count < 1000)
                    $this->replyWithMessage([
                        'text' => format(config("tgbot.templates.pet"), [
                            'count' => $count,
                        ])
                    ]);
                else
                    $this->replyWithMessage([
                        'text' => format(config("tgbot.templates.pet_out"), [
                            'count' => $count,
                        ])
                    ]);
            } catch (\Exception $e) {
                \Log::error($e);
                try {
                    if ($count < 1000)
                        $this->replyWithMessage([
                            'text' => format(config("tgbot.templates.pet"), [
                                'count' => $count,
                            ])
                        ]);
                    else
                        $this->replyWithMessage([
                            'text' => format(config("tgbot.templates.pet_out"), [
                                'count' => $count,
                            ])
                        ]);
                } catch (\Exception $e) {
                    \Log::error($e);
                }
            }
        }else{
            try{
                Telegram::deleteMessage([
                    'chat_id' => $this->update->getMessage()->getChat()->getId(),
                    'message_id' => $this->update->getMessage()->getMessageId(),
                ]);
            }catch (\Exception $e){
                \Log::error($e);
                \Log::debug([
                    'chat_id' => $this->update->getMessage()->getChat()->getId(),
                    'message_id' => $this->update->getMessage()->getMessageId(),
                ]);
            }
            $sentCount = Pet::where('created_at','>',Carbon::now()->addMinutes(-1)->toDateTimeString())
                ->where('is_messaged',1)->where('chat_id','<',0)->count();
            if($sentCount > 19){
                ;
                // Ignore.
            }else{
                $time = Pet::orderBy('id','desc')->where('is_messaged',1)->first();
                if($time)
                    $time = time() - Carbon::createFromTimeString($time->updated_at)->getTimestamp();
                else
                    $time = 1;
                $coun = Pet::where('is_sent',0)->where('chat_id','<',0)->count();

                Pet::where('is_sent',0)->where('chat_id','<',0)->update([
                        'is_sent' => 1,
                    ]);

                $pet->is_messaged = 1;
                $pet->save();
                if($coun <= 1){
                    $temp = 'pet';
                }else{
                    $temp = 'pet_1';
                }
                $message = null;
                try {
                    if ($count < 1000)
                        $message = $this->replyWithMessage([
                            'text' => format(config("tgbot.templates.$temp"), [
                                'count' => $count,
                                'count_1' => $coun,
                                'time' => $time,
                            ])
                        ]);
                    else
                        $message = $this->replyWithMessage([
                            'text' => format(config("tgbot.templates.pet_out"), [
                                'count' => $count,
                            ])
                        ]);
                } catch (\Exception $e) {
                    \Log::alert($e);
                }
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                $result = $redis->multi()->lpop("telegram")->lpush('telegram',json_encode([
                    $message->getMessageId(),
                    $this->update->getMessage()->getChat()->getId()
                ]))->exec();
                $result = json_decode($result[0], true);
                try{
                    Telegram::deleteMessage([
                        'chat_id'=>$result[1],
                        'message_id' => $result[0],
                    ]);
                }catch (\Exception $e){
                    \Log::error($e);
                }
            }
        }
        return;
    }
}
