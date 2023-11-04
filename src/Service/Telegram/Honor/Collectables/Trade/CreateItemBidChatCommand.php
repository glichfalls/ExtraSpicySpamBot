<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\Item\ItemAuction;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractItemTelegramCallbackQuery;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

final class CreateItemBidChatCommand extends AbstractItemTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'trade:bid';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $data = $this->getCallbackDataParts($update, 2);
        $bid = (int) array_pop($data);
        $instanceId = array_pop($data);
        $instance = $this->itemService->getInstance($instanceId);
        if ($instance === null) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Item not found.', true);
            return;
        }
        $auction = $this->collectableService->getActiveAuction($instance);
        if ($auction === null) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'No active auction found.', true);
            return;
        }
        $bid = $auction->getHighestBid() + $bid;
        $honor = $this->honorService->getCurrentHonorAmount($chat, $user);
        if ($honor < $bid) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'You dont have enough Ehre');
            return;
        }
        $auction->setHighestBidder($user);
        $auction->setHighestBid($bid);
        $this->manager->flush();
        $this->telegramService->sendText(
            $chat->getChatId(),
            sprintf('%s bid %s Ehre for %s', $user->getName(), NumberFormat::format($auction->getHighestBid()), $instance->getItem()->getName()),
            threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            replyMarkup: $this->getKeyboard($auction),
        );
        $this->telegramService->deleteMessage(
            $chat->getChatId(),
            $update->getCallbackQuery()->getMessage()->getMessageId(),
        );
        $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
    }

    private function getKeyboard(ItemAuction $auction): InlineKeyboardMarkup
    {
        $keyboard = [];
        $data = sprintf('%s:%s', CreateItemBidChatCommand::CALLBACK_KEYWORD, $auction->getInstance()->getId());
        $options = [1000, 100_000, 1_000_000];
        $row = [];
        foreach ($options as $option) {
            $row[] = [
                'text' => sprintf('+%s', NumberFormat::format($option)),
                'callback_data' => sprintf('%s:%s', $data, $option),
            ];
        }
        $keyboard[] = $row;
        $keyboard[] = [
            [
                'text' => 'Accept',
                'callback_data' => sprintf('%s:%s', AcceptItemTradeChatCommand::CALLBACK_KEYWORD, $auction->getInstance()->getId()),
            ],
            [
                'text' => 'Decline',
                'callback_data' => sprintf('%s:%s', DeclineItemTradeChatCommand::CALLBACK_KEYWORD, $auction->getInstance()->getId()),
            ],
        ];
        return new InlineKeyboardMarkup($keyboard);
    }

}