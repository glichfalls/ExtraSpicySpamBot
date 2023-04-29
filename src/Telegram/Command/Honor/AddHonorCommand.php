<?php

namespace App\Telegram\Command\Honor;

use App\Repository\UserRepository;
use App\Service\HonorService;
use App\Service\TelegramBaseService;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class AddHonorCommand extends AbstractHonorCommand
{

    public function __construct(
        LoggerInterface $logger,
        TelegramBaseService $telegramService,
        HonorService $honorService,
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
        return ['honor', 'ehre'];
    }

    public function execute(BotApi $api, Update $update): void
    {
        $message = $this->telegramService->createMessageFromUpdate($update);
        $parameter = $this->getCommandParameters($update);
        $this->logger->info($parameter);

        $users = $this->userRepository->getUsersByChat($message->getChat());

        $buttons = ['test'];

        foreach ($users as $user) {
            $buttons[] = [$user->getUsername()];
        }

        $keyboard = new ReplyKeyboardMarkup($buttons, true);

        $api->sendMessage(
            $message->getChat()->getId(),
            'test',
            replyToMessageId: $message->getMessageId(),
            replyMarkup: $keyboard,
        );
    }
}