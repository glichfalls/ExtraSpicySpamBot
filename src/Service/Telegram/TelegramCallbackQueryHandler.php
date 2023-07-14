<?php

namespace App\Service\Telegram;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use TelegramBot\Api\Types\Update;

class TelegramCallbackQueryHandler
{

    /**
     * @var iterable<TelegramCallbackQueryListener>
     */
    private iterable $listeners;

    public function __construct(
        #[TaggedIterator('telegram.inline_query')] iterable $telegramChatCommands,
        private TelegramService $telegramService,
    )
    {
        $this->listeners = $telegramChatCommands;
    }

    public function handle(Update $update): void
    {
        $callbackQuery = $update->getCallbackQuery();
        $data = $callbackQuery->getData();
        $chat = $this->telegramService->getChatFromUpdate($update);
        $user = $this->telegramService->getSenderFromUpdate($update);
        if ($chat === null || $user === null) {
            return;
        }
        foreach ($this->listeners as $listener) {
            if (str_contains($data, $listener->getCallbackKeyword())) {
                $listener->handleCallback($update, $chat, $user);
            }
        }
    }

}