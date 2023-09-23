<?php

namespace App\Service\Telegram\Honor\Collectables;

use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\Message\Message;
use App\Service\Telegram\Honor\Collectables\Trade\ShowCollectableInfoChatCommand;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

final class ShowCollectionChatCommand extends AbstractCollectableTelegramChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!collection/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $collection = $this->getCollection($message->getChat(), $message->getUser());
        $this->telegramService->sendText(
            $message->getChat()->getChatId(),
            sprintf('%s\'s collection', $message->getUser()->getName()),
            threadId: $message->getTelegramThreadId(),
            replyMarkup: $this->getKeyboards($collection),
        );
    }

    /**
     * @param CollectableItemInstance[] $collectables
     * @return array
     */
    public function getKeyboards(array $collectables): InlineKeyboardMarkup
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
        $this->logger->debug('Keyboard', ['keyboard' => $keyboard]);
        return new InlineKeyboardMarkup($keyboard);
    }

}
