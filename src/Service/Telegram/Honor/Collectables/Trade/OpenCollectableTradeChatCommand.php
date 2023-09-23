<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Telegram\Honor\Collectables\AbstractCollectableTelegramChatCommand;
use App\Service\Telegram\TelegramCallbackQueryListener;
use TelegramBot\Api\Types\Update;

class OpenCollectableTradeChatCommand extends AbstractCollectableTelegramChatCommand implements TelegramCallbackQueryListener
{

    public const CALLBACK_KEYWORD = 'collectable:trade';

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!trade/i', $message->getMessage()) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $mentions = $this->telegramService->getUsersFromMentions($update);

        if (count($mentions) !== 1) {
            $this->telegramService->replyTo($message, 'You need to mention one user to trade with.');
            return;
        }
    }

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
        $message = <<<MESSAGE
        @%s wants to buy %s
        Enter your price suggestion to start the trade.
        MESSAGE;
        $this->telegramService->sendText($chat->getChatId(), sprintf($message, $user->getName()));
    }

}