<?php

namespace App\Service;

use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramBot\Api\Types\MessageEntity;
use TelegramBot\Api\Types\Update;

class HonorService
{

    public function __construct(
        private LoggerInterface $logger,
        private BotApi $api,
        private UserService $userService,
        private EntityManagerInterface $manager
    )
    {

    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function handle(Update $update, Message $message): void
    {

        $text = $message->getMessage();

        $this->logger->info($text);

        if (preg_match('/^\(?<op>+|\-)\s*(?<count>\d+)\s*ehre\s*@(?<name>.+)$/i', $text, $matches) === 1) {

            $operation = $matches['op'];
            $name = $matches['name'];
            $count = (int) $matches['count'];
            if ($operation === '-') {
                $count *= -1;
            }

            if ($count <= 0 || $count > 10) {
                $this->api->sendMessage(
                    $update->getMessage()->getChat()->getId(),
                    'nei lol',
                    replyToMessageId: $update->getMessage()->getMessageId(),
                );
                return;
            }

            /** @var MessageEntity[] $entities */
            $entities = $update->getMessage()->getEntities();

            foreach ($entities as $entity) {
                switch ($entity->getType()) {
                    case MessageEntity::TYPE_MENTION:
                        $recipient = $this->userService->getByName(
                            substr($text, $entity->getOffset() + 1, $entity->getLength() - 1)
                        );
                        break;
                    case MessageEntity::TYPE_TEXT_MENTION:
                        $recipient = $this->userService->createUserFromMessageEntity($entity);
                        break;
                    default: continue 2;
                }

                $this->logger->info(sprintf('Found mention %s', $entity->toJson()));

                if ($recipient === null) {
                    $this->api->sendMessage(
                        $update->getMessage()->getChat()->getId(),
                        sprintf('User %s not found', $name),
                        replyToMessageId: $update->getMessage()->getMessageId(),
                    );
                    continue;
                }

                if ($recipient->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
                    if ($message->getUser()->getName() !== 'glichfalls') {
                        $this->api->sendMessage(
                            $update->getMessage()->getChat()->getId(),
                            'xd!',
                            replyToMessageId: $update->getMessage()->getMessageId(),
                        );
                        return;
                    }
                    $this->api->sendMessage(
                        $update->getMessage()->getChat()->getId(),
                        'hehe',
                        replyToMessageId: $update->getMessage()->getMessageId(),
                    );
                }

                $honor = HonorFactory::create($message->getChat(), $message->getUser(), $recipient, $count);
                $this->manager->persist($honor);
                $this->manager->flush();
                $this->api->sendMessage(
                    $update->getMessage()->getChat()->getId(),
                    sprintf('User %s received %d Ehre', $name, $count),
                    replyToMessageId: $update->getMessage()->getMessageId(),
                );
            }
        }

        if (preg_match('/^!(honor|ehre)/i', $text) === 1) {
            $honors = $message->getUser()->getHonor();
            $total = array_reduce($honors->toArray(), fn($carry, $item) => $carry + $item->getAmount(), 0);
            $responseText = sprintf('You have %d Ehre', $total);
            $this->api->sendMessage($update->getMessage()->getChat()->getId(), $responseText, replyToMessageId: $update->getMessage()->getMessageId());
        }

    }

}