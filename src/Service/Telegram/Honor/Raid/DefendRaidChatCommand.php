<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Raid;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Telegram\TelegramCallbackQueryListener;
use TelegramBot\Api\Types\Update;

class DefendRaidChatCommand extends AbstractRaidChatCommand implements TelegramCallbackQueryListener
{
    public const CALLBACK_KEYWORD = 'raid:defend';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        try {
            $raid = $this->raidService->defendRaid($chat, $user);
            $this->telegramService->sendText(
                $chat->getChatId(),
                $this->translator->trans('telegram.raid.userDefendingRaid', [
                    'name' => $user->getName(),
                ]),
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            );
            $this->telegramService->answerCallbackQuery(
                $update->getCallbackQuery(),
                $this->translator->trans('telegram.raid.nowDefendingRaid'),
                false,
            );
            $this->telegramService->changeInlineKeyboard(
                $update->getCallbackQuery()->getMessage()->getChat()->getId(),
                $update->getCallbackQuery()->getMessage()->getMessageId(),
                $this->getRaidKeyboard($raid),
            );
        } catch (\RuntimeException $exception) {
            $this->logger->info(sprintf('failed to handle callback query [%s]', $exception->getMessage()));
            $this->telegramService->answerCallbackQuery(
                $update->getCallbackQuery(),
                $exception->getMessage(),
                true,
            );
        }
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(defend|d)$/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $this->raidService->defendRaid($message->getChat(), $message->getUser());
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.nowDefendingRaid'));
        } catch (\RuntimeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
            return;
        }
    }

    public function getSyntax(): string
    {
        return '!defend or !d';
    }

    public function getDescription(): string
    {
        return 'defend the raid';
    }

}