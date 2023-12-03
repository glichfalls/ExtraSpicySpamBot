<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Raid;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Telegram\TelegramCallbackQueryListener;
use TelegramBot\Api\Types\Update;

class SupportRaidChatCommand extends AbstractRaidChatCommand implements TelegramCallbackQueryListener
{
    public const CALLBACK_KEYWORD = 'raid:support';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        try {
            $raid = $this->raidService->supportRaid($chat, $user);
            $this->telegramService->sendText(
                $chat->getChatId(),
                $this->translator->trans('telegram.raid.userSupportingRaid', [
                    'name' => $user->getName(),
                ]),
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            );
            $this->telegramService->answerCallbackQuery(
                $update->getCallbackQuery(),
                'You are now supporting the raid',
                false,
            );
            $this->telegramService->changeInlineKeyboard(
                $update->getCallbackQuery()->getMessage()->getChat()->getId(),
                $update->getCallbackQuery()->getMessage()->getMessageId(),
                $this->getRaidKeyboard($raid),
            );
        } catch (\RuntimeException $exception) {
            $this->logger->info($exception->getMessage());
            $this->telegramService->answerCallbackQuery(
                $update->getCallbackQuery(),
                $exception->getMessage(),
                true,
            );
        }
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(support|s)$/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $this->raidService->supportRaid($message->getChat(), $message->getUser());
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.nowSupportingRaid'));
        } catch (\RuntimeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        }
    }

    public function getSyntax(): string
    {
        return '!support or !s';
    }

    public function getDescription(): string
    {
        return 'Support the active raid';
    }

}