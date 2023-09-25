<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractCollectableTelegramCallbackQuery;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Update;

class BuyCollectableChatCommand extends AbstractCollectableTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'collectable:buy';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $data = explode(':', $update->getCallbackQuery()->getData());
        $collectableId = array_pop($data);
        $collectable = $this->collectableService->getInstanceById($collectableId);
        if ($collectable === null) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Collectable not found.', true);
            return;
        }
        try {
            $this->collectableService->buyCollectable($collectable, $user);
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
            $this->telegramService->sendText($chat->getChatId(), sprintf(
                '%s bought %s for %s Ehre',
                $user->getName() ?? $user->getFirstName(),
                $collectable->getCollectable()->getName(),
                NumberFormat::format($collectable->getPrice()),
            ));
        } catch (\RuntimeException $exception) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), $exception->getMessage(), true);
        }
    }

}