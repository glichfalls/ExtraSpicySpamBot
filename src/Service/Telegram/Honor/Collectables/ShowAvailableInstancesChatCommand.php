<?php

namespace App\Service\Telegram\Honor\Collectables;

use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\Message\Message;
use App\Service\Telegram\Honor\Collectables\Trade\ShowCollectableInfoChatCommand;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class ShowAvailableInstancesChatCommand extends AbstractCollectableTelegramChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!items/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $collectables = $this->collectableService->getAvailableInstances($message->getChat());
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
     * @param CollectableItemInstance[] $collectables
     * @return InlineKeyboardMarkup
     */
    private function getKeyboard(array $collectables): InlineKeyboardMarkup
    {
        $keyboard = [];
        $row = [];
        foreach ($collectables as $collectable) {
            $data = sprintf('%s:%s', ShowCollectableInfoChatCommand::CALLBACK_KEYWORD, $collectable->getId());
            $row[] = [
                'text' => $collectable->getCollectable()->getName(),
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