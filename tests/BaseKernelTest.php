<?php

namespace App\Tests;

use App\Entity\Chat\Chat;
use App\Entity\Chat\ChatFactory;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class BaseKernelTest extends KernelTestCase
{

    protected function getLogger(): LoggerInterface
    {
        $container = static::getContainer();
        return $container->get(LoggerInterface::class);
    }

    public function getTranslator(): TranslatorInterface
    {
        $container = static::getContainer();
        return $container->get(TranslatorInterface::class);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function createTestChat(int $id): Chat
    {
        return ChatFactory::create($id, sprintf('Test Chat %d', $id));
    }

    protected function createTestUser(int $id): User
    {
        $user = new User();
        $user->setName(sprintf('Test User %d', $id));
        $user->setFirstName(sprintf('Test First Name %d', $id));
        $user->setLastName(sprintf('Test Last Name %d', $id));
        $user->setTelegramUserId($id);
        return $user;
    }

}