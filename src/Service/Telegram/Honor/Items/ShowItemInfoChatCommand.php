<?php

namespace App\Service\Telegram\Honor\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemAttribute;
use App\Entity\Item\Effect\Effect;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
use App\Service\Items\ItemEffectService;
use App\Service\Items\ItemService;
use App\Service\Telegram\AbstractTelegramCallbackQuery;
use App\Service\Telegram\Button\TelegramButton;
use App\Service\Telegram\Button\TelegramKeyboard;
use App\Service\Telegram\Honor\Items\Trade\OpenItemTradeChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class ShowItemInfoChatCommand extends AbstractTelegramCallbackQuery
{
    public const CALLBACK_KEYWORD = 'item:show';

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly ItemService $itemService,
        private readonly ItemEffectService $itemEffectService,
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
        $text = <<<TEXT
        %s
        %s
        rarity: %s
        owner %s
        %s
        TEXT;
        $effects = $instance->getItem()->getEffects()->map(fn (Effect $effect) => $effect->getDescription())->getValues();
        $message = sprintf(
            $text,
            $instance->getItem()->getName(),
            $instance->getItem()->getDescription(),
            $instance->getItem()->getRarity()->emoji(),
            $instance->getOwner()?->getName() ?? 'nobody',
            count($effects) > 0 ? implode(PHP_EOL, $effects) : 'no effects',
        );
        if ($instance->getItem()->getImagePublicPath() !== null) {
            $fullPath = sprintf('https://%s/%s', $_SERVER['HTTP_HOST'], $instance->getItem()->getImagePublicPath());
            $this->telegramService->sendImage(
                $chat->getChatId(),
                $fullPath,
                caption: $message,
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                replyMarkup: $this->getKeyboard($instance),
            );
        } else {
            $this->telegramService->sendText(
                $chat->getChatId(),
                $message,
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                replyMarkup: $this->getKeyboard($instance),
            );
        }
        $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
    }

    private function getKeyboard(ItemInstance $instance): ?InlineKeyboardMarkup
    {
        $buttons = new TelegramKeyboard();
        $buttons->add(new TelegramButton('Trade', sprintf('%s:%s', OpenItemTradeChatCommand::CALLBACK_KEYWORD, $instance->getId())));
        foreach ($instance->getItem()->getAttributes() as $attribute) {
            if ($attribute == ItemAttribute::Executable) {
                $buttons->add(new TelegramButton($instance->getPayloadValue('executable_name') ?? 'execute', sprintf(
                    '%s:%s',
                    ItemExecutionChatCommand::CALLBACK_KEYWORD,
                    $instance->getId(),
                )));
            }
        }
        return $this->telegramService->createKeyboard($buttons);
    }

}