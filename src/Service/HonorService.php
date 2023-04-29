<?php

namespace App\Service;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\HonorRepository;
use App\Repository\UserRepository;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use TelegramBot\Api\Types\Update;

class HonorService
{

    public function __construct(
        private TelegramBaseService $telegramService,
        private HonorRepository $honorRepository,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
        private EntityManagerInterface $manager,
    )
    {

    }

    public function handle(Update $update, Message $message): void
    {

        $text = $message->getMessage();

        $this->logger->info($text);

        if (preg_match('/^(?<op>[+\-])\s*(?<count>\d+)\s*ehre\s*@(?<name>.+)$/i', $text, $matches) === 1) {

            $operation = $matches['op'];
            $name = $matches['name'];
            $count = (int) $matches['count'];

            if ($operation === '-') {
                $count *= -1;
            }

            $recipients = $this->telegramService->getUsersFromMentions($update);

            foreach ($recipients as $recipient) {

                if ($recipient === null) {
                    $this->telegramService->replyTo($message, sprintf('User %s not found', $name));
                    continue;
                }

                $this->applyHonor($message, $recipient, $count);

            }
        }

        if (preg_match('/^!(honor|ehre)/i', $text) === 1) {
            $this->showHonor($message);
        }

    }

    public function showHonor(Message $message): void
    {
        $total = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        $this->telegramService->replyTo($message, sprintf('You have %d Ehre', $total));
    }

    public function showLeaderboard(Message $message): void
    {
        $leaderboard = $this->honorRepository->getLeaderboard($message->getChat());
        if (count($leaderboard) === 0) {
            $this->telegramService->replyTo($message, 'no leaderboard yet');
        } else {
            $text = array_map(function ($entry) {
                $name = $entry['firstName'] ?? $entry['name'];
                return sprintf('%s: %d Ehre',  $name, $entry['amount']);
            }, $leaderboard);
            $text = implode(PHP_EOL, $text);
            $this->telegramService->replyTo($message, $text);
        }
    }

    public function applyHonor(Message $message, User $recipient, int $amount): void
    {
        if ($amount < -3 || $amount > 3) {
            $this->telegramService->replyTo($message, 'xddd!!111');
            return;
        }

        if ($recipient->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
            if ($message->getUser()->getName() !== 'glichfalls') {
                $this->telegramService->replyTo($message, ':^)');
                return;
            }
        }

        $timeSinceLastChange = $this->getTimeSinceLastChange($message->getUser(), $recipient, $message->getChat());
        if ($this->isRateLimited($timeSinceLastChange)) {
            $waitTime = 5 - $timeSinceLastChange->i;
            $this->telegramService->replyTo($message, sprintf('wait %s more minutes', $waitTime));
            return;
        }

        $honor = HonorFactory::create($message->getChat(), $message->getUser(), $recipient, $amount);
        $this->manager->persist($honor);
        $this->manager->flush();
        $this->telegramService->replyTo($message, sprintf('User %s received %d Ehre', $recipient->getName(), $amount));
    }

    public function getTimeSinceLastChange(User $sender, User $recipient, Chat $chat): ?DateInterval
    {
        $lastChange = $this->honorRepository->getLastChange($sender, $recipient, $chat);
        return $lastChange?->getCreatedAt()->diff(new \DateTime());
    }

    public function isRateLimited(?DateInterval $timeSinceLastChange): bool
    {
        return $timeSinceLastChange !== null && $timeSinceLastChange->i < 5;
    }

}