<?php

namespace App\Service;

use App\Entity\User\User;
use App\Entity\User\UserFactory;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use TelegramBot\Api\Types\MessageEntity;
use \TelegramBot\Api\Types\User as TelegramUser;

class UserService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private UserRepository $userRepository
    ) {

    }

    public function getByName(string $name): ?User
    {
        return $this->userRepository->findOneBy(['name' => $name]);
    }

    public function getUserByTelegramId(int $id): ?User
    {
        return $this->userRepository->getByTelegramId($id);
    }

    public function createUserFromMessageEntity(MessageEntity $entity): ?User
    {
        if ($entity->getType() !== MessageEntity::TYPE_TEXT_MENTION || $entity->getUser()?->getId() === null) {
            return null;
        }
        $user = $this->userRepository->getByTelegramId($entity->getUser()->getId());
        return $user ?: $this->create($entity->getUser());
    }

    private function create(TelegramUser $user): User
    {
        $user = UserFactory::createFromTelegramUser($user);
        $this->manager->persist($user);
        return $user;
    }

}
