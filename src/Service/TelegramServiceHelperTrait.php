<?php

namespace App\Service;

use App\Entity\Chat\Chat;
use App\Entity\Chat\ChatFactory;
use App\Entity\Message\Message;
use App\Entity\Message\MessageFactory;
use App\Entity\User\User;
use App\Entity\User\UserFactory;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;
use \TelegramBot\Api\Types\Message as TelegramMessage;

trait TelegramServiceHelperTrait
{


    public function __construct(
        protected BotApi $bot,
        protected ChatRepository $chatRepository,
        protected MessageRepository $messageRepository,
        protected UserRepository $userRepository)
    {
        if ($_ENV['APP_ENV'] === 'dev') {
            $this->bot->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
        }
    }

    protected function setBot(BotApi $bot): void
    {
        $this->bot = $bot;

    }

    public function replyTo(
        Message $message,
        string $text,
        ?ReplyKeyboardMarkup $replyMarkup = null,
        $parseMode = null,
    ): TelegramMessage
    {
        return $this->bot->sendMessage(
            $message->getChat()->getChatId(),
            $text,
            parseMode: $parseMode,
            replyToMessageId: $message->getTelegramMessageId(),
            replyMarkup: $replyMarkup,
        );
    }

    public function createMessageFromUpdate(Update $update): Message
    {
        if (!$update->getMessage()?->getText()) {
            throw new \RuntimeException('message not found');
        }
        $chat = $this->getChatFromUpdate($update);
        $sender = $this->getSenderFromUpdate($update);
        if (!$chat || !$sender) {
            throw new \RuntimeException('chat or sender not found');
        }
        $message = MessageFactory::create($chat, $sender, $update->getMessage());
        $this->manager->persist($message);
        $this->manager->flush();
        return $message;
    }

    private function getChatFromUpdate(Update $update): ?Chat
    {
        if ($update->getMessage()?->getChat()?->getId() === null) {
            return null;
        }
        $chat = $this->chatRepository->getChatByTelegramId($update->getMessage()->getChat()->getId());
        if (!$chat) {
            $chat = ChatFactory::createFromUpdate($update);
            $this->manager->persist($chat);
            $this->manager->flush();
        }
        return $chat;
    }

    private function getSenderFromUpdate(Update $update): ?User
    {
        if ($update->getMessage()?->getFrom()?->getId() === null) {
            return null;
        }
        /** @var User|null $user */
        $user = $this->userRepository->getByTelegramId($update->getMessage()->getFrom()->getId());
        if (!$user) {
            $user = UserFactory::createFromTelegramUser($update->getMessage()->getFrom());
            $this->manager->persist($user);
            return $user;
        }
        if ($user->getName() !== $update->getMessage()->getFrom()->getUsername()) {
            $user->setName($update->getMessage()->getFrom()->getUsername());
            $user->setFirstName($update->getMessage()->getFrom()->getFirstName());
            $this->manager->flush();
        }
        return $user;
    }

}