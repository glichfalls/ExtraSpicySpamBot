<?php

namespace App\Service\Telegram\Honor\Items\Trade;

use App\Entity\Chat\Chat;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use App\Service\Items\ItemService;
use App\Service\Items\ItemTradeService;
use App\Service\Telegram\AbstractTelegramCallbackQuery;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class OpenItemTradeChatCommand extends AbstractTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'trade:open';

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly ItemService $itemService,
        private readonly ItemTradeService $itemTradeService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $instance = $this->itemService->getInstance($this->getCallbackDataId($update));
        if ($instance === null) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Item not found.', true);
            return;
        }
        $activeAuction = $this->itemTradeService->getActiveAuction($instance);
        if ($activeAuction === null) {
            $this->itemTradeService->createAuction($instance);
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