<?php

namespace App\Service;

use App\Entity\Honor\HonorFactory;
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

            $this->logger->info('matches');
            $name = $matches['name'];
            $count = (int) $matches['count'];

            /** @var User $user */
            $user = $this->userRepository->findOneBy(['name' => $name]);

            if ($user) {

                if ($user->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
                    $this->api->sendMessage($update->getMessage()->getChat()->getId(), 'xd!', replyToMessageId: $message->getMessageId());
                    return;
                }

                $honor = HonorFactory::create($message->getChat(), $message->getUser(), $user, $count);
                $this->manager->persist($honor);
                $this->manager->flush();
                $this->api->sendMessage($update->getMessage()->getChat()->getId(), sprintf('User %s got %d Ehre', $name, $count));
            } else {
                $this->api->sendMessage($update->getMessage()->getChat()->getId(), sprintf('User %s not found', $name));
            }

        }

        if (preg_match('/^!honor/i', $text) === 1) {
            $honors = $message->getUser()->getHonor();
            $total = array_reduce($honors->toArray(), fn($carry, $item) => $carry + $item->getAmount(), 0);
            $responseText = sprintf('You have %d Ehre', $total);
            $this->api->sendMessage($update->getMessage()->getChat()->getId(), $responseText, replyToMessageId: $message->getMessageId());
        }

    }

}