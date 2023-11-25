<?php declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Chat\ChatConfig;
use App\Entity\Chat\ChatFactory;
use App\Entity\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;

/**
 * Used to load test data into the database
 */
class TelegramFixtures extends Fixture
{

    public function load(ObjectManager $manager): void
    {
        /** @var Connection $connection */
        $connection = $manager->getConnection();
        $connection->setAutoCommit(true);
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setTelegramUserId(mt_rand(1_000, 1_000_000_000));
            $user->setName(sprintf('Test User %d', $i));
            $user->setFirstName(sprintf('Test %d', $i));
            $user->setLastName(sprintf('User %d', $i));
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());
            $users[] = $user;
            $manager->persist($user);
        }
        for ($i = 1; $i <= 10; $i++) {
            $chat = ChatFactory::create((string)mt_rand(1_000, 1_000_000_000), sprintf('Test Chat %d', $i));
            $chat->setConfig(new ChatConfig());
            for ($j = 1; $j <= mt_rand(1, 5); $j++) {
                $chat->addUser($users[mt_rand(0, count($users) - 1)]);
            }
            $manager->persist($chat);
        }
        $manager->flush();
    }
}