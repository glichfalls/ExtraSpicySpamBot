<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\User\User;
use App\Service\Telegram\Collectables\CollectableService;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use TelegramBot\Api\Types\Update;

class ShowCollectableInfoChatCommand implements TelegramCallbackQueryListener
{

    public const CALLBACK_KEYWORD = 'collectable:show';

    public function __construct(
        private TelegramService $telegram,
        private CollectableService $collectableService,
    ) {
    }

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
            $this->telegram->answerCallbackQuery($update->getCallbackQuery(), 'Collectable not found.', true);
            return;
        }
        $this->telegram->sendText(
            $chat->getChatId(),
            sprintf(
                "%s\nOwner: %s\nBuy Price: %s",
                $collectable->getCollectable()->getName(),
                $collectable->getOwner()?->getName() ?? 'Nobody',
                $collectable->getCurrentTransaction()?->getPrice() ?? '-',
            ),
            replyMarkup: $this->getKeyboard($collectable),
        );
    }

    private function getKeyboard(CollectableItemInstance $collectable): array
    {
        $keyboard = [];
        $row = [];
        $row[] = [
            'text' => 'Trade',
            'callback_data' => sprintf(
                '%s:%s',
                OpenCollectableTradeChatCommand::CALLBACK_KEYWORD,
                $collectable->getId(),
            ),
        ];
        $keyboard[] = $row;
        return $keyboard;
    }

}
