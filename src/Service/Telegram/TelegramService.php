<?php

namespace App\Service\Telegram;

use App\Entity\Chat\Chat;
use App\Entity\Chat\ChatFactory;
use App\Entity\Message\Message;
use App\Entity\Message\MessageFactory;
use App\Entity\User\User;
use App\Entity\User\UserFactory;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia;
use TelegramBot\Api\Types\InputMedia\InputMediaVideo;
use TelegramBot\Api\Types\Message as TelegramMessage;
use TelegramBot\Api\Types\MessageEntity;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class TelegramService
{

    public function __construct(
        protected BotApi $bot,
        protected LoggerInterface $logger,
        protected UserService $userService,
        protected EntityManagerInterface $manager,
        protected ChatRepository $chatRepository,
        protected MessageRepository $messageRepository,
        protected UserRepository $userRepository,
    )
    {
        if ($_ENV['APP_ENV'] === 'dev') {
            $this->bot->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
        }
    }

    /**
     * @param Update $update
     * @return array<User>
     */
    public function getUsersFromMentions(Update $update): array
    {
        $users = [];
        /** @var MessageEntity $entity */
        foreach ($update->getMessage()->getEntities() as $entity) {
            $user = match ($entity->getType()) {
                MessageEntity::TYPE_MENTION => $this->userService->getByName(
                    substr(
                        $update->getMessage()->getText(),
                        $entity->getOffset() + 1,
                        $entity->getLength() - 1
                    )
                ),
                MessageEntity::TYPE_TEXT_MENTION => $this->userService->createUserFromMessageEntity($entity),
                default => null,
            };
            if ($user !== null) {
                $this->logger->info(sprintf('Found mention %s', $entity->toJson()));
                $users[] = $user;
            }
        }
        return $users;
    }

    public function sendVideo(string $chatId, string $url): ?array
    {
        $media = new ArrayOfInputMedia();
        $media->addItem(new InputMediaVideo($url));
        return $this->bot->sendMediaGroup($chatId, $media);
    }

    public function sendText(string $chatId, string $text): ?TelegramMessage
    {
        return $this->bot->sendMessage($chatId, $text);
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

    public function videoReplyTo(
        Message $message,
        string $videoUrl,
    ): TelegramMessage
    {
        return $this->bot->sendVideo(
            $message->getChat()->getChatId(),
            $videoUrl,
            replyToMessageId: $message->getTelegramMessageId(),
        );
    }

    public function imageReplyTo(Message $message, string $imageUrl): TelegramMessage
    {
        return $this->bot->sendPhoto(
            $message->getChat()->getChatId(),
            $imageUrl,
            replyToMessageId: $message->getTelegramMessageId(),
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