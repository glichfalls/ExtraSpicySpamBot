<?php

namespace App\Service\Telegram\Honor\Raid;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Telegram\TelegramCallbackQueryListener;
use TelegramBot\Api\Types\Update;

class CancelRaidChatCommand extends AbstractRaidChatCommand implements TelegramCallbackQueryListener
{
    public const CALLBACK_KEYWORD = 'raid:cancel';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        try {
            $this->raidService->cancelRaid($chat, $user);
            $this->telegramService->sendText(
                $chat->getChatId(),
                $this->translator->trans('telegram.raid.raidCanceled'),
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            );
            $this->telegramService->answerCallbackQuery(
                $update->getCallbackQuery(),
                'Raid has been canceled',
                false,
            );
        } catch (\RuntimeException $exception) {
            $this->telegramService->answerCallbackQuery(
                $update->getCallbackQuery(),
                $exception->getMessage(),
                true,
            );
        }
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!cancel raid/', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $this->raidService->cancelRaid($message->getChat(), $message->getUser());
            $this->telegramService->sendText(
                $message->getChat()->getChatId(),
                $this->translator->trans('telegram.raid.raidCanceled'),
                threadId: $message->getTelegramThreadId(),
            );
        } catch (\RuntimeException $exception) {
            $this->telegramService->replyTo(
                $message,
                $exception->getMessage(),
            );
        }
    }

    public function getSyntax(): string
    {
        return '!cancel raid';
    }

    public function getDescription(): string
    {
        return 'cancels the active raid';
    }

}