<?php

namespace App\Service\Telegram;

use App\Entity\Chat\Chat;
use App\Entity\User\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TelegramBot\Api\Types\Update;

#[AutoconfigureTag('telegram.inline_query')]
interface TelegramCallbackQueryListener
{

    /**
     * @return string
     */
    public function getCallbackKeyword(): string;

    /**
     * @param Update $update
     * @param Chat $chat
     * @param User $user
     * @return void
     */
    public function handleCallback(Update $update, Chat $chat, User $user): void;

}