<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\Item\ItemInstance;
use App\Entity\Item\Effect\Effect;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractItemTelegramCallbackQuery;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class ShowItemInfoChatCommand extends AbstractItemTelegramCallbackQuery
{
    public const CALLBACK_KEYWORD = 'collectable:show';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $instance = $this->collectableService->getInstanceById($this->getCallbackDataId($update));
        if ($instance === null) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Item not found.', true);
            return;
        }
        $text = <<<TEXT
        %s
        %s
        rarity: %s
        owner %s
        %s
        TEXT;
        $effects = $instance->getItem()->getEffects()->map(fn (Effect $effect) => $effect->getDescription())->getValues();
        $message = sprintf(
            $text,
            $instance->getItem()->getName(),
            $instance->getItem()->getDescription(),
            $instance->getItem()->getRarity()->emoji(),
            $instance->getOwner()?->getName() ?? 'nobody',
            count($effects) > 0 ? implode(PHP_EOL, $effects) : 'no effects',
        );
        if ($instance->getItem()->getImagePublicPath() !== null) {
            $fullPath = sprintf('https://%s/%s', $_SERVER['HTTP_HOST'], $instance->getItem()->getImagePublicPath());
            $this->telegramService->sendImage(
                $chat->getChatId(),
                $fullPath,
                caption: $message,
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                replyMarkup: $this->getKeyboard($instance),
            );
        } else {
            $this->telegramService->sendText(
                $chat->getChatId(),
                $message,
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                replyMarkup: $this->getKeyboard($instance),
            );
        }
        $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
    }

    private function getKeyboard(ItemInstance $collectable): ?InlineKeyboardMarkup
    {
        return $this->createKeyboard([
            [
                'text' => 'Trade',
                'callback_data' => sprintf(
                    '%s:%s',
                    OpenItemTradeChatCommand::CALLBACK_KEYWORD,
                    $collectable->getId(),
                ),
            ],
        ]);
    }

}
