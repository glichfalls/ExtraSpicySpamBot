<?php

namespace App\Service\Telegram\Honor\Collectables\Trade;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use TelegramBot\Api\Types\Update;

class AcceptCollectableTradeChatCommand implements TelegramCallbackQueryListener
{

    public const CALLBACK_KEYWORD = 'collectable:trade:accept';

    public function __construct(private TelegramService $telegram)
    {
    }

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {



    }

}