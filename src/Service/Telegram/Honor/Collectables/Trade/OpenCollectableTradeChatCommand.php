<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractCollectableTelegramCallbackQuery;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class OpenCollectableTradeChatCommand extends AbstractCollectableTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'trade:open';

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
        $activeAuction = $this->collectableService->getActiveAuction($collectable);
        if ($activeAuction === null) {
            $this->collectableService->createAuction($collectable);
            $format = <<<MESSAGE
            @%s: someone wants to buy %s
            
            You can now start bidding.
            MESSAGE;
            $message = sprintf($format, $collectable->getOwner()->getName(), $collectable->getCollectable()->getName());
        } else {
            $message = sprintf('current bid: %s Ehre', NumberFormat::format($activeAuction->getHighestBid()));
        }
        $this->telegramService->sendText(
            $chat->getChatId(),
            $message,
            threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            replyMarkup: $this->getKeyboard($collectable),
        );
        $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
    }

    private function getKeyboard(CollectableItemInstance $instance): InlineKeyboardMarkup
    {
        $keyboard = [];
        $data = sprintf('%s:%s', CreateCollectableBidChatCommand::CALLBACK_KEYWORD, $instance->getId());
        $options = [1_000, 10_000, 100_000];
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