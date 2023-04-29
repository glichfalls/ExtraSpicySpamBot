<?php

namespace App\Service;

use App\Entity\User\User;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\InputMedia\ArrayOfInputMedia;
use TelegramBot\Api\Types\InputMedia\InputMediaVideo;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\MessageEntity;
use TelegramBot\Api\Types\Update;

class TelegramBaseService
{
    use TelegramServiceHelperTrait {
        TelegramServiceHelperTrait::__construct as private __telegramServiceHelperTraitConstruct;
    }

    public function __construct(
        BotApi $bot,
        protected LoggerInterface $logger,
        protected UserService $userService,
        protected EntityManagerInterface $manager,
        protected ChatRepository $chatRepository,
        protected MessageRepository $messageRepository,
        protected UserRepository $userRepository,
    )
    {
        $this->__telegramServiceHelperTraitConstruct($bot, $chatRepository, $messageRepository, $userRepository);
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

    public function sendText(string $chatId, string $text): ?Message
    {
        return $this->bot->sendMessage($chatId, $text);
    }

}