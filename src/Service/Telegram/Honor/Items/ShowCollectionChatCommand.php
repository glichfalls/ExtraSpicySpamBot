<?php

namespace App\Service\Telegram\Honor\Items;

use App\Entity\Item\ItemInstance;
use App\Entity\Message\Message;
use App\Service\Items\ItemService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\Button\TelegramButton;
use App\Service\Telegram\Button\TelegramKeyboard;
use App\Service\Telegram\TelegramService;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

final class ShowCollectionChatCommand extends AbstractTelegramChatCommand
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
        return $this->telegramService->createKeyboard(new TelegramKeyboard($instances->map(fn (ItemInstance $instance) => new TelegramButton(
            sprintf('%s %s', $instance->getItem()->getRarity()->emoji(), $instance->getItem()->getName()),
            sprintf('%s:%s', ShowItemInfoChatCommand::CALLBACK_KEYWORD, $instance->getId()),
        ))->toArray()));
    }

}
