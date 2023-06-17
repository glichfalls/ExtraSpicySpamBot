<?php

namespace App\Telegram\Command\Honor;

use App\Entity\User\User;
use App\Repository\UserRepository;
use App\Service\HonorService;
use App\Service\Telegram\TelegramService;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class AddHonorCommand extends AbstractHonorCommand
{

    public function __construct(
        LoggerInterface        $logger,
        TelegramService        $telegramService,
        HonorService           $honorService,
        private UserRepository $userRepository,
    )
    {
        parent::__construct($logger, $telegramService, $honorService);
    }

    public function getName(): string
    {
        return 'ehre';
    }

    public function getDescription(): string
    {
        return 'add or remove honor form a user';
    }

    public function getAliases(): array
    {
        return ['/honor', '/ehre'];
    }

    public function execute(BotApi $api, Update $update): void
    {
        return;
        $message = $this->telegramService->createMessageFromUpdate($update);

        $users = $this->userRepository->getUsersByChat($message->getChat());

        $buttons = array_map(fn(User $user) => [$user->getName()], $users);

        // fill buttons, 2 per row
        $buttons = array_chunk($buttons, 2);

        $keyboard = new ReplyKeyboardMarkup($buttons, true);

        $this->telegramService->replyTo($message, 'select user', replyMarkup: $keyboard);
    }
}