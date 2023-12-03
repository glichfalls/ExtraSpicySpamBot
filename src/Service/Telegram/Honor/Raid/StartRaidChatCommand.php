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
        try {
            $result = $this->raidService->executeRaid($chat, $user);
            $raid = $result->raid;
            if ($result->success) {
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    $this->translator->trans('telegram.raid.raidSuccessful', [
                        'target' => $raid->getTarget()->getName(),
                        'honorCount' => $raid->getAmount(),
                    ]),
                    threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                );
            } else {
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    $this->translator->trans('telegram.raid.raidFailed', [
                        'target' => $raid->getTarget()->getName(),
                    ]),
                    threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                );
            }
            $this->telegramService->answerCallbackQuery(
                $update->getCallbackQuery(),
                'Raid finished',
                false,
            );
            $this->telegramService->deleteMessage(
                $update->getCallbackQuery()->getMessage()->getChat()->getId(),
                $update->getCallbackQuery()->getMessage()->getMessageId(),
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
                        'honorCount' => NumberFormat::money($result->raid->getAmount()),
                    ]),
                );
            } else {
                $this->telegramService->replyTo(
                    $message,
                    $this->translator->trans('telegram.raid.raidFailed', [
                        'target' => $result->raid->getTarget()->getName() ?? $result->raid->getTarget()->getFirstName(),
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
