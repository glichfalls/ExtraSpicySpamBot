<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Raid;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Update;

class StartRaidChatCommand extends AbstractRaidChatCommand implements TelegramCallbackQueryListener
{
    public const CALLBACK_KEYWORD = 'raid:start';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $callbackQuery = $update->getCallbackQuery();
        if (null === $callbackQuery) {
            throw new \RuntimeException('Callback query is missing');
        }
        $threadId = $callbackQuery->getMessage()?->getMessageThreadId();
        try {
            $result = $this->raidService->executeRaid($chat, $user);
            if ($result->success) {
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    $this->translator->trans('telegram.raid.raidSuccessful', [
                        'target' => $result->raid->getTarget()->getName(),
                        'successRate' => $this->raidService->getSuccessChance($result->raid),
                    ]),
                    threadId: $threadId,
                );
            } else {
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    $this->translator->trans('telegram.raid.raidFailed', [
                        'target' => $result->raid->getTarget()->getName(),
                        'successRate' => $this->raidService->getSuccessChance($result->raid),
                    ]),
                    threadId: $threadId,
                );
            }
            $this->telegramService->answerCallbackQuery($callbackQuery, 'Raid finished');
            $this->telegramService->deleteMessage(
                $chat->getChatId(),
                (int) $callbackQuery->getMessage()?->getMessageId(),
            );
        } catch (\RuntimeException $exception) {
            $this->logger->info($exception->getMessage());
            $this->telegramService->answerCallbackQuery(
                $callbackQuery,
                $exception->getMessage(),
                true,
            );
        }
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!start raid$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $result = $this->raidService->executeRaid($message->getChat(), $message->getUser());
            if ($result->success) {
                $this->telegramService->replyTo(
                    $message,
                    $this->translator->trans('telegram.raid.raidSuccessful', [
                        'target' => $result->raid->getTarget()->getName(),
                        'successRate' => $this->raidService->getSuccessChance($result->raid),
                    ]),
                );
            } else {
                $this->telegramService->replyTo(
                    $message,
                    $this->translator->trans('telegram.raid.raidFailed', [
                        'target' => $result->raid->getTarget()->getName(),
                        'successRate' => $this->raidService->getSuccessChance($result->raid),
                    ]),
                );
            }
        } catch (\RuntimeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        }
    }

    public function getSyntax(): string
    {
        return '!start raid';
    }

    public function getDescription(): string
    {
        return 'start the active raid';
    }

}
