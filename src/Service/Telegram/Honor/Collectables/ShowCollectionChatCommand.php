<?php

namespace App\Service\Telegram\Honor\Collectables;

use App\Entity\Item\ItemInstance;
use App\Entity\Message\Message;
use App\Service\Telegram\Honor\Collectables\Trade\ShowItemInfoChatCommand;
use Doctrine\Common\Collections\Collection;
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
        $collection = $this->itemService->getInstanceCollection($message->getChat(), $message->getUser());
        $this->telegramService->sendText(
            $message->getChat()->getChatId(),
            sprintf('%s\'s collection', $message->getUser()->getName()),
            threadId: $message->getTelegramThreadId(),
            replyMarkup: $this->getKeyboards($collection),
        );
    }

    /**
     * @param Collection<ItemInstance> $instances
     * @return InlineKeyboardMarkup
     */
    public function getKeyboards(Collection $instances): InlineKeyboardMarkup
    {
        $keyboard = [];
        $row = [];
        foreach ($instances as $instance) {
            $data = sprintf('%s:%s', ShowItemInfoChatCommand::CALLBACK_KEYWORD, $instance->getId());
            $row[] = [
                'text' => $instance->getItem()->getName(),
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
