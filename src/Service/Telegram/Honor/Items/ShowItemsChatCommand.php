<?php

namespace App\Service\Telegram\Honor\Items;

use App\Entity\Item\ItemInstance;
use App\Entity\Message\Message;
use App\Service\Items\ItemService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class ShowItemsChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly ItemService $itemService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!items/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $collectables = $this->itemService->getAvailableInstances($message->getChat());
        if (count($collectables) === 0) {
            $this->telegramService->replyTo($message, 'No items available.');
        } else {
            $this->telegramService->sendText(
                $message->getChat()->getChatId(),
                'Available items:',
                threadId: $message->getTelegramThreadId(),
                replyMarkup: $this->getKeyboard($collectables)
            );
        }
    }

    /**
     * @param ItemInstance[] $collectables
     * @return InlineKeyboardMarkup
     */
    private function getKeyboard(array $collectables): InlineKeyboardMarkup
    {
        $keyboard = [];
        $row = [];
        foreach ($collectables as $collectable) {
            $data = sprintf('%s:%s', ShowItemInfoChatCommand::CALLBACK_KEYWORD, $collectable->getId());
            $row[] = [
                'text' => $collectable->getItem()->getName(),
                'callback_data' => $data,
            ];
            if (count($row) === 3) {
                $keyboard[] = $row;
                $row = [];
            }
        }
        if (count($row) > 0) {
            $keyboard[] = $row;
        }
        return new InlineKeyboardMarkup($keyboard);
    }

}