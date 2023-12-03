<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Items\Trade;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Service\Items\ItemService;
use App\Service\Items\ItemTradeService;
use App\Service\Telegram\AbstractTelegramCallbackQuery;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class AcceptItemTradeChatCommand extends AbstractTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'trade:accept';

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
        if ($instance->getOwner() !== $user) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'You are not the owner of this item.', true);
            return;
        }
        try {
            $auction = $this->itemTradeService->getActiveAuction($instance);
            if ($auction === null) {
                $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'No active auction found.', true);
                return;
            }
            $this->itemTradeService->acceptAuction($auction);
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Auction accepted.', true);
            $this->telegramService->sendText(
                $chat->getChatId(),
                sprintf(
                    '%s sold! %s paid %s Ehre to %s.',
                    $instance->getItem()->getName(),
                    $auction->getHighestBidder()->getName(),
                    NumberFormat::money($auction->getHighestBid()),
                    $auction->getSeller()->getName(),
                ),
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            );
            $this->telegramService->deleteMessage(
                $chat->getChatId(),
                $update->getCallbackQuery()->getMessage()->getMessageId(),
            );
        } catch (\RuntimeException $e) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), $e->getMessage(), true);
            return;
        }
    }

}