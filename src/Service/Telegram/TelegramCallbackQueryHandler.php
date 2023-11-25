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
        $message = $callbackQuery->getMessage();
        if ($message === null) {
            return;
        }
        $messageEntity = $this->telegramService->createMessageFromUpdate($update);
        $chat = $this->telegramService->getChatFromMessage($message);
        $user = $this->telegramService->getUserFromCallbackQuery($callbackQuery);
        if ($chat === null || $user === null) {
            $this->telegramService->sendText(
                $update->getCallbackQuery()->getMessage()->getChat()->getId(),
                'Something went wrong, please try again later.');
            return;
        }
        foreach ($this->listeners as $listener) {
            if (str_contains($data, $listener->getCallbackKeyword())) {
                $listener->handleCallback($update, $chat, $user);
            }
        }
    }

}