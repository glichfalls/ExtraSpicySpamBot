<?php

namespace App\Service;

use App\Entity\User\User;
use App\Entity\User\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use TelegramBot\Api\Types\MessageEntity;
use TelegramBot\Api\Types\Update;
use \TelegramBot\Api\Types\User as TelegramUser;

class UserService
{

    private EntityRepository $userRepository;

    public function __construct(private EntityManagerInterface $manager)
    {
        $this->userRepository = $this->manager->getRepository(User::class);
    }

    public function createSender(Update $update): ?User
    {
        if ($update->getMessage()?->getFrom()?->getId() === null) {
            return null;
        }
        $user = $this->userRepository->findOneBy(['telegramUserId' => $update->getMessage()->getFrom()->getId()]);
        return $user ?: $this->create($update->getMessage()->getFrom());
    }

    public function createUserFromMessageEntity(MessageEntity $entity): ?User
    {
        if ($entity->getType() !== MessageEntity::TYPE_TEXT_MENTION || $entity->getUser()?->getId() === null) {
            return null;
        }
        $user = $this->userRepository->findOneBy(['telegramUserId' => $entity->getUser()?->getId()]);
        return $user ?: $this->create($entity->getUser());
    }

    private function create(TelegramUser $user): User
    {
        $user = UserFactory::createFromTelegramUser($user);
        $this->manager->persist($user);
        return $user;
    }

}