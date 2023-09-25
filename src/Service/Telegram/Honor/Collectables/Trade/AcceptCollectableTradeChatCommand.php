<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractCollectableTelegramCallbackQuery;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Update;

class AcceptCollectableTradeChatCommand extends AbstractCollectableTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'trade:accept';

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
        try {
            $auction = $this->getAuction($collectable);
            $transaction = $this->collectableService->acceptAuction($auction);
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Auction accepted.', true);
            $this->telegramService->sendText(
                $chat->getChatId(),
                sprintf(
                    '%s sold! %s paid %s Ehre to %s.',
                    $collectable->getCollectable()->getName(),
                    $transaction->getBuyer()->getName(),
                    NumberFormat::format($transaction->getPrice()),
                    $transaction->getSeller()->getName(),
                ),
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            );
        } catch (\RuntimeException $e) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), $e->getMessage(), true);
            return;
        }
    }

}