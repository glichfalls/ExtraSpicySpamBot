<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractItemTelegramCallbackQuery;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class OpenItemTradeChatCommand extends AbstractItemTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'trade:open';

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
        $activeAuction = $this->collectableService->getActiveAuction($instance);
        if ($activeAuction === null) {
            $this->collectableService->createAuction($instance);
            $message = sprintf(
                '@%s: someone wants to buy %s',
                $instance->getOwner()->getName(),
                $instance->getItem()->getName()
            );
        } else {
            $message = sprintf('current bid: %s Ehre', NumberFormat::format($activeAuction->getHighestBid()));
        }
        $this->telegramService->sendText(
            $chat->getChatId(),
            $message,
            threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            replyMarkup: $this->getKeyboard($instance),
        );
        $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
    }

    private function getKeyboard(ItemInstance $instance): InlineKeyboardMarkup
    {
        $keyboard = [];
        $data = sprintf('%s:%s', CreateItemBidChatCommand::CALLBACK_KEYWORD, $instance->getId());
        $options = [1_000, 10_000, 100_000, 1_000_000];
        $row = [];
        foreach ($options as $option) {
            $row[] = [
                'text' => sprintf('+%s', NumberFormat::format($option)),
                'callback_data' => sprintf('%s:%s', $data, $option),
            ];
        }
        $keyboard[] = $row;
        return new InlineKeyboardMarkup($keyboard);
    }

}