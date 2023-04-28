<?php

namespace App\Service;

use App\Entity\Message\Message;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramBot\Api\Types\Update;

class HonorService
{

    private EntityRepository $userRepository;

    public function __construct(
        private LoggerInterface $logger,
        private BotApi $api,
        private EntityManagerInterface $manager
    )
    {
        $this->userRepository = $this->manager->getRepository(User::class);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function handle(Update $update, Message $message): void
    {

        $text = $message->getMessage();

        $this->logger->info($text);

        if (preg_match('/^\+\s*(?<count>\d+)\s*ehre\s*@(?<name>.+)$/i', $text, $matches) === 1) {
            $name = $matches['name'];
            $count = (int) $matches['count'];

            /** @var User $user */
            $user = $this->userRepository->findOneBy(['username' => $name]);

            if ($user) {
                $user->setHonor($user->getHonor() + $count);
                $this->manager->persist($user);
                $this->manager->flush();
                $this->api->sendMessage($update->getMessage()->getChat()->getId(), "Ehre +{$count} für {$name}!");
            } else {
                $this->api->sendMessage($update->getMessage()->getChat()->getId(), "User {$name} nicht gefunden!");
            }

        }

    }

}