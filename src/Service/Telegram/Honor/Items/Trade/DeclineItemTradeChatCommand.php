<?php

namespace App\Service\Telegram\Honor\Items\Trade;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Service\Items\ItemService;
use App\Service\Items\ItemTradeService;
use App\Service\Telegram\AbstractTelegramCallbackQuery;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class DeclineItemTradeChatCommand extends AbstractTelegramCallbackQuery
{

    public const CALLBACK_KEYWORD = 'trade:decline';

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
        try {
            $this->itemTradeService->declineItemAuction($instance, $user);
            $this->manager->flush();
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Auction declined.', true);
            $this->telegramService->sendText(
                $chat->getChatId(),
                sprintf(
                    '%s declined the auction for %s.',
                    $user->getName(),
                    $instance->getItem()->getName(),
                ),
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
            );
            $this->telegramService->deleteMessage(
                $chat->getChatId(),
                $update->getCallbackQuery()->getMessage()->getMessageId(),
            );
        } catch (\RuntimeException $exception) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), $exception->getMessage(), true);
        }
    }

}
