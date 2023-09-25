<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\CollectableAuction;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractCollectableTelegramCallbackQuery;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

final class CreateCollectableBidChatCommand extends AbstractCollectableTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'trade:bid';

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
        $auction = $this->getAuction($collectable);
        $bid = $auction->getHighestBid() + $bid;
        $honor = $this->honorRepository->getHonorCount($user, $chat);
        if ($honor < $bid) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'You dont have enough Ehre');
            return;
        }
        $auction->setHighestBidder($user);
        $auction->setHighestBid($auction->getHighestBid() + $bid);
        $this->manager->flush();
        $this->telegramService->sendText(
            $chat->getChatId(),
            sprintf('%s bid %s Ehre for %s', $user->getName(), NumberFormat::format($auction->getHighestBid()), $collectable->getCollectable()->getName()),
            threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            replyMarkup: $this->getKeyboard($auction),
        );
        $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
    }

    private function getKeyboard(CollectableAuction $auction): InlineKeyboardMarkup
    {
        $keyboard = [];
        $data = sprintf('%s:%s', CreateCollectableBidChatCommand::CALLBACK_KEYWORD, $auction->getInstance()->getId());
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
                'callback_data' => sprintf('%s:%s', AcceptCollectableTradeChatCommand::CALLBACK_KEYWORD, $auction->getInstance()->getId()),
            ],
            [
                'text' => 'Decline',
                'callback_data' => sprintf('%s:%s', DeclineCollectableTradeChatCommand::CALLBACK_KEYWORD, $auction->getInstance()->getId()),
            ],
        ];
        return new InlineKeyboardMarkup($keyboard);
    }

}