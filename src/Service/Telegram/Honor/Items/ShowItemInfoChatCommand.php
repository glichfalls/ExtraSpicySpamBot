<?php

namespace App\Service\Telegram\Honor\Items;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemAttribute;
use App\Entity\Item\Effect\EffectApplicable;
use App\Entity\Item\ItemInstance;
use App\Entity\User\User;
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
        private readonly string $appHost
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        try {
            $instance = $this->itemService->getInstance($this->getCallbackDataId($update));
            $text = <<<TEXT
            {$instance->getItem()->getName()}
            {$instance->getItem()->getDescription()}
            {$instance->getItem()->getRarity()->name()}
            %s
            TEXT;
            $effects = $instance->getItem()->getEffects()->map(fn (EffectApplicable $effect) => $effect->getDescription())->getValues();
            $message = sprintf($text, count($effects) > 0 ? implode(PHP_EOL, $effects) : 'no effects');
            if ($instance->getOwner() !== null) {
                $message .= sprintf(
                    '%sOwner: %s',
                    PHP_EOL,
                    $instance->getOwner()->getName() ?? $instance->getOwner()->getFirstName()
                );
            }
            if ($instance->getExpiresAt()) {
                $message .= sprintf(
                    '%sExpires in %s',
                    PHP_EOL,
                    $instance->getExpiresAt()->diff(new \DateTime())->format('%a days')
                );
            }
            if ($instance->getItem()->getImagePublicPath() !== null) {
                $this->telegramService->sendImage(
                    $chat->getChatId(),
                    $instance->getItem()->getImagePublicUrl($this->appHost),
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
        } catch(\Throwable $th) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Something went wrong.', true);
            $this->logger->error($th->getMessage());
        }
    }

    private function getKeyboard(ItemInstance $instance): ?InlineKeyboardMarkup
    {
        if ($instance->getOwner() === null) {
            return null;
        }
        $buttons = new TelegramKeyboard();
        $buttons->add(new TelegramButton('Trade', sprintf('%s:%s', OpenItemTradeChatCommand::CALLBACK_KEYWORD, $instance->getId())));
        foreach ($instance->getItem()->getAttributes() as $attribute) {
            if ($attribute == ItemAttribute::Executable) {
                $label = $instance->getPayloadValue('executable_name') ?? 'execute';
                $buttons->add(new TelegramButton($label, sprintf(
                    '%s:%s',
                    ItemExecutionChatCommand::CALLBACK_KEYWORD,
                    $instance->getId(),
                )));
            }
        }
        return $this->telegramService->createKeyboard($buttons);
    }

}
