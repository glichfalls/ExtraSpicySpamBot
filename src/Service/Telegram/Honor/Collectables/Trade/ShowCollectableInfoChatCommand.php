<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\Collectable\CollectableItemInstance;
use App\Entity\Collectable\Effect\Effect;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractCollectableTelegramCallbackQuery;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class ShowCollectableInfoChatCommand extends AbstractCollectableTelegramCallbackQuery
{
    public const CALLBACK_KEYWORD = 'collectable:show';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $data = explode(':', $update->getCallbackQuery()->getData());
        $collectableId = array_pop($data);
        $collectable = $this->collectableService->getInstanceById($collectableId);
        if ($collectable === null) {
            $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'Collectable not found.', true);
            return;
        }
        $text = <<<TEXT
        %s
        %s
        owner: %s
        Effect(s): %s
        TEXT;
        $effects = $collectable->getCollectable()->getEffects()->map(fn (Effect $effect) => $effect->getDescription())->getValues();
        $message = sprintf(
            $text,
            $collectable->getCollectable()->getName(),
            $collectable->getCollectable()->getDescription(),
            $collectable->getOwner()?->getName() ?? 'Nobody',
            count($effects) > 0 ? implode(PHP_EOL, $effects) : 'None',
        );
        if ($collectable->getCollectable()->getImagePublicPath() !== null) {
            $fullPath = sprintf('https://%s/%s', $_SERVER['HTTP_HOST'], $collectable->getCollectable()->getImagePublicPath());
            $this->telegramService->sendImage(
                $chat->getChatId(),
                $fullPath,
                caption: $message,
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                replyMarkup: $this->getKeyboard($collectable),
            );
        } else {
            $this->telegramService->sendText(
                $chat->getChatId(),
                $message,
                threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                replyMarkup: $this->getKeyboard($collectable),
            );
        }
        $this->telegramService->answerCallbackQuery($update->getCallbackQuery());
    }

    private function getKeyboard(CollectableItemInstance $collectable): ?InlineKeyboardMarkup
    {
        if (!$collectable->getCollectable()->isTradeable()) {
            return null;
        }
        if ($collectable->getOwner() === null) {
            return $this->createKeyboard([
                [
                    'text' => sprintf('Buy (%s Ehre)', NumberFormat::format($collectable->getPrice())),
                    'callback_data' => sprintf(
                        '%s:%s',
                        BuyCollectableChatCommand::CALLBACK_KEYWORD,
                        $collectable->getId(),
                    ),
                ],
            ]);
        }
        return $this->createKeyboard([
            [
                'text' => 'Trade',
                'callback_data' => sprintf(
                    '%s:%s',
                    OpenCollectableTradeChatCommand::CALLBACK_KEYWORD,
                    $collectable->getId(),
                ),
            ],
        ]);
    }

}
