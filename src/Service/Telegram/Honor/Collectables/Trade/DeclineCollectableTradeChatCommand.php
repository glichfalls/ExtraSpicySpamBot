<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractCollectableTelegramCallbackQuery;
use TelegramBot\Api\Types\Update;

class DeclineCollectableTradeChatCommand extends AbstractCollectableTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'trade:decline';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $data = explode(':', $update->getCallbackQuery()->getData());
        if (count($data) !== 3) {
            throw new \InvalidArgumentException('Invalid callback data for collectable bid.');
        }
        $collectableId = array_pop($data);
        $collectable = $this->collectableService->getInstanceById($collectableId);
        if ($collectable === null) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Collectable not found.', true);
            return;
        }
        if ($collectable->getOwner() !== $user) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'You are not the owner of this collectable.', true);
            return;
        }
        $auction = $this->collectableService->getActiveAuction($collectable);
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
                $collectable->getCollectable()->getName(),
            ),
            threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
        );
        $this->telegramService->deleteMessage(
            $chat->getChatId(),
            $update->getCallbackQuery()->getMessage()->getMessageId(),
        );
    }

}
