<?php

namespace App\Service\Telegram;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use TelegramBot\Api\Types\Update;

class TelegramWebhookHandler
{

    /**
     * @var iterable<TelegramChatCommand>
     */
    private iterable $handlers;

    public function __construct(
        #[TaggedIterator('telegram.chat_command')]
        iterable $telegramChatCommands,
        private readonly TelegramService $telegramBaseService,
    ) {
        $this->handlers = $telegramChatCommands;
    }

    public function handle(Update $update): void
    {
        if ($update->getMessage()->getChat()) {
            $message = $this->telegramBaseService->createMessageFromUpdate($update);
            foreach ($this->handlers as $telegramChatCommand) {
                $matches = [];
                if ($telegramChatCommand->matches($update, $message, $matches)) {
                    $telegramChatCommand->handle($update, $message, $matches);
                }
            }
        }
    }

}
