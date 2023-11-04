<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractItemTelegramCallbackQuery;
use TelegramBot\Api\Types\Update;

class DeclineItemTradeChatCommand extends AbstractItemTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'trade:decline';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $instance = $this->itemService->getInstance($this->getCallbackDataId($update));
        if ($instance === null) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Item not found.', true);
            return;
        }
        if ($instance->getOwner() !== $user) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'You are not the owner of this collectable.', true);
            return;
        }
        $auction = $this->collectableService->getActiveAuction($instance);
        if ($auction === null) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'No active auction found.', true);
            return;
        }
        $auction->setActive(false);
        $auction->setUpdatedAt(new \DateTime());
        $this->manager->flush();
        $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Auction declined.', true);
        $this->telegramService->sendText(
            $chat->getChatId(),
            sprintf(
                '%s declined the auction for %s.',
                $user->getName(),
                $collectable->getItem()->getName(),
            ),
            threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
        );
        $this->telegramService->deleteMessage(
            $chat->getChatId(),
            $update->getCallbackQuery()->getMessage()->getMessageId(),
        );
    }

}
