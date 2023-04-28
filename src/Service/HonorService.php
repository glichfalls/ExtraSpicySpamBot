<?php

namespace App\Service;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramBot\Api\Types\Update;

class HonorService
{

    private EntityRepository $userRepository;

    public function __construct(private BotApi $api, private EntityManagerInterface $manager)
    {
        $this->userRepository = $this->manager->getRepository(User::class);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function handle(Message $message): void
    {

        $text = $message->getMessage();

        if (preg_match('/^\+\s*(?<count>\d+)\s*ehre\s*@(?<name>.+)$/i', $text, $matches) === 1) {
            $name = $matches['name'];
            $count = (int) $matches['count'];

            /** @var User $user */
            $user = $this->userRepository->findOneBy(['username' => $name]);

            if ($user) {
                $user->setHonor($user->getHonor() + $count);
                $this->manager->persist($user);
                $this->manager->flush();
                $this->api->sendMessage($message->getChat()->getChatId(), "Ehre +{$count} für {$name}!");
            } else {
                $this->api->sendMessage($message->getChat()->getChatId(), "User {$name} nicht gefunden!");
            }

        }

    }

}