<?php


namespace App\Commands;


use Telegram\Bot\Commands\Command;

/**
 * Class HelpCommand.
 */
class DebugCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'debug';

    /**
     * @var string Command Description
     */
    protected $description = 'Debug Command.';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        $this->replyWithMessage([
            'text' => json_encode($this->update->getMessage()),
            'reply_to_message_id' => $this->update->getMessage()->getMessageId()
        ]);
        return;
    }
}
