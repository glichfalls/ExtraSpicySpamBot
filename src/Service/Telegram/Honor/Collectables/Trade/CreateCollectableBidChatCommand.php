<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractCollectableTelegramCallbackQuery;
use TelegramBot\Api\Types\Update;

final class CreateCollectableBidChatCommand extends AbstractCollectableTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'collectable:bid';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $data = explode(':', $update->getCallbackQuery()->getData());
        if (count($data) !== 4) {
            $this->logger->error('Invalid callback data for collectable bid.', [
                'data' => $data,
            ]);
        }
        $bid = (int) array_pop($data);
        $collectableId = array_pop($data);
        $collectable = $this->collectableService->getInstanceById($collectableId);
        if ($collectable === null) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Collectable not found.', true);
            return;
        }

    }

}