<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\User\User;
use App\Service\Telegram\AbstractTelegramCallbackQuery;
use App\Service\Telegram\Collectables\CollectableService;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class ShowCollectableInfoChatCommand extends AbstractTelegramCallbackQuery
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
        $text = <<<TEXT
        %s
        %s
        owner: %s
        TEXT;
        $message = sprintf(
            $text,
            $collectable->getCollectable()->getName(),
            $collectable->getCollectable()->getDescription(),
            $collectable->getOwner()?->getName() ?? 'Nobody',
        );
        if ($collectable->getCollectable()->getImagePublicPath() !== null) {
            $fullPath = sprintf('https://%s/%s', $_SERVER['HTTP_HOST'], $collectable->getCollectable()->getImagePublicPath());
            $this->telegram->sendImage(
                $chat->getChatId(),
                $fullPath,
                caption: $message,
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                replyMarkup: $this->getKeyboard($collectable),
            );
        } else {
            $this->telegram->sendText(
                $chat->getChatId(),
                $message,
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                replyMarkup: $this->getKeyboard($collectable),
            );
        }
        $this->telegram->answerCallbackQuery($update->getCallbackQuery());
    }

    private function getKeyboard(CollectableItemInstance $collectable): ?InlineKeyboardMarkup
    {
        if (!$collectable->getCollectable()->isTradeable()) {
            return null;
        }
        if ($collectable->getOwner() === null) {
            $this->createKeyboard([
                [
                    'text' => sprintf('Buy (%s Ehre)', NumberFormat::format($collectable->getPrice())),
                    'callback_data' => sprintf(
                        '%s:%s',
                        BuyCollectableChatCommand::CALLBACK_KEYWORD,
                        $collectable->getId(),
                    ),
                ],
            ]);
        }
        return $this->createKeyboard([
            [
                'text' => 'Trade',
                'callback_data' => sprintf(
                    '%s:%s',
                    OpenCollectableTradeChatCommand::CALLBACK_KEYWORD,
                    $collectable->getId(),
                ),
            ],
        ]);
    }

}
