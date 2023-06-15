<?php

namespace App\Service;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\Raid\RaidFactory;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\HonorRepository;
use App\Repository\RaidRepository;
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
        private RaidRepository $raidRepository,
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

        if (preg_match('/^!raid\s*@(?<name>.+)$/i', $text, $matches) === 1) {
            $name = $matches['name'];
            $targets = $this->telegramService->getUsersFromMentions($update);
            foreach ($targets as $target) {
                if ($target === null) {
                    $this->telegramService->replyTo($message, sprintf('User %s not found', $name));
                    continue;
                }
                $this->raid($message, $target);
            }
        }

        if (preg_match('/^!support/i', $text) === 1) {
            $this->supportRaid($message);
        }

        if (preg_match('/^!defend/i', $text) === 1) {
            $this->defendRaid($message);
        }

        if (preg_match('/^!start raid$/i', $text) === 1) {
            $this->startRaid($message);
        }

        if (preg_match('/^!cancel raid$/i', $text) === 1) {
            $this->startRaid($message);
        }

    }

    public function showHonor(Message $message): void
    {
        $total = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        $this->telegramService->replyTo($message, sprintf('You have %d Ehre', $total));
    }

    public function showLeaderboard(Message $message, $reply = true): void
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
            if ($reply) {
                $this->telegramService->replyTo($message, $text);
            } else {
                $this->telegramService->sendText($message->getChat()->getChatId(), $text);
            }
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

    public function raid(Message $message, User $target): void
    {
        $chat = $message->getChat();
        if ($this->raidRepository->hasActiveRaid($chat)) {
            $this->telegramService->replyTo($message, 'raid already active');
            return;
        }
        $targetHonorCount = $this->honorRepository->getHonorCount($target, $chat);
        if ($targetHonorCount <= 0) {
            $this->telegramService->replyTo($message, 'target has no honor, no raid possible :(');
            return;
        }
        $raid = RaidFactory::create($chat, $message->getUser(), $target);
        $this->manager->persist($raid);
        $this->manager->flush();
        $this->telegramService->sendText($chat->getChatId(), 'Welcome to RAID: Shadow Legends! (Beta)');
        $this->telegramService->sendText($chat->getChatId(), sprintf(
            '%s started a raid against %s! to join write !support and to defend write !defend. To start the raid write !start raid',
            $message->getUser()->getName(),
            $target->getName()
        ));
    }

    public function supportRaid(Message $message): void
    {
        $raid = $this->raidRepository->getActiveRaid($message->getChat());
        if ($raid === null) {
            $this->telegramService->replyTo($message, 'no active raid');
            return;
        }
        if ($raid->getTarget()->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, 'you cannot support your own raid');
            return;
        }
        if ($raid->getLeader()->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, 'the raid leader automatically supports the raid');
            return;
        }
        if ($raid->getSupporters()->filter(fn(User $user) => $user->getTelegramUserId() === $message->getUser()->getTelegramUserId())->count() > 0) {
            $this->telegramService->replyTo($message, 'you already support the raid');
            return;
        }
        if ($raid->getDefenders()->filter(fn(User $user) => $user->getTelegramUserId() === $message->getUser()->getTelegramUserId())->count() > 0) {
            $this->telegramService->replyTo($message, 'you cannot support and defend the raid');
            return;
        }
        $raid->getSupporters()->add($message->getUser());
        $this->manager->persist($raid);
        $this->manager->flush();
        $this->telegramService->replyTo($message, 'you are now supporting the raid');
        $this->telegramService->sendText($message->getChat()->getChatId(), sprintf(
            '%s are now supporting the raid against %s, %s are defending',
            $raid->getSupporters()->count(),
            $raid->getTarget()->getName(),
            $raid->getDefenders()->count(),
        ));
    }

    public function defendRaid(Message $message): void
    {
        $raid = $this->raidRepository->getActiveRaid($message->getChat());
        if ($raid === null) {
            $this->telegramService->replyTo($message, 'no active raid');
            return;
        }
        if ($raid->getTarget()->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, 'you cannot defend your own raid');
            return;
        }
        if ($raid->getLeader()->getTelegramUserId() === $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, 'the raid leader cannot defend the raid');
            return;
        }
        if ($raid->getDefenders()->filter(fn(User $user) => $user->getTelegramUserId() === $message->getUser()->getTelegramUserId())->count() > 0) {
            $this->telegramService->replyTo($message, 'you already defend the raid');
            return;
        }
        if ($raid->getSupporters()->filter(fn(User $user) => $user->getTelegramUserId() === $message->getUser()->getTelegramUserId())->count() > 0) {
            $this->telegramService->replyTo($message, 'you cannot support and defend the raid');
            return;
        }
        $raid->getDefenders()->add($message->getUser());
        $this->manager->persist($raid);
        $this->manager->flush();
        $this->telegramService->replyTo($message, 'you are now defending the raid');
        $this->telegramService->sendText($message->getChat()->getChatId(), sprintf(
            '%s are now supporting the raid against %s, %s are defending',
            $raid->getSupporters()->count(),
            $raid->getTarget()->getName(),
            $raid->getDefenders()->count(),
        ));
    }

    public function cancelRaid(Message $message): void
    {
        $raid = $this->raidRepository->getActiveRaid($message->getChat());
        if ($raid === null) {
            $this->telegramService->replyTo($message, 'no active raid');
            return;
        }
        if ($raid->getLeader()->getTelegramUserId() !== $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, 'you are not the raid leader');
            return;
        }
        $raid->setIsActive(false);
        $this->manager->flush();
        $this->telegramService->sendText($message->getChat()->getChatId(), 'raid cancelled');
    }

    public function startRaid(Message $message): void
    {
        $raid = $this->raidRepository->getActiveRaid($message->getChat());
        if ($raid === null) {
            $this->telegramService->replyTo($message, 'no active raid');
            return;
        }
        if ($raid->getLeader()->getTelegramUserId() !== $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, 'you are not the raid leader');
            return;
        }

        $supporterCount = $raid->getSupporters()->count();
        $defenderCount = $raid->getDefenders()->count();

        if ($supporterCount === 0) {
            $this->telegramService->replyTo($message, 'cannot start raid without supporters');
            return;
        }

        $targetHonorCount = $this->honorRepository->getHonorCount($raid->getTarget(), $message->getChat());

        $this->telegramService->sendText($message->getChat()->getChatId(), sprintf('
            raid against %s started with %s supporters and %s defenders, %s honor will be stolen and equally 
            distributed among the supporters if the raid is successful. the defenders will receive %s honor from 
            the supporters if the raid fails.
            ',
            $raid->getTarget()->getName(),
            $supporterCount,
            $defenderCount,
            $targetHonorCount,
        ));

        // leader + supporters - defenders
        $chance = 1 + $supporterCount - $defenderCount;

        // supporters + defenders + leader + target
        $totalParticipants = $supporterCount + $defenderCount + 2;

        $chancePercentage = $chance / $totalParticipants * 100;
        $this->telegramService->sendText($message->getChat()->getChatId(), sprintf('%s%% chance to succeed', $chancePercentage));

        if (random_int(1, $totalParticipants) >= $chance) {
            $this->telegramService->sendText($message->getChat()->getChatId(), sprintf('
                raid against %s was successful! %s honor was stolen and equally distributed among the supporters and leader.
                ',
                $raid->getTarget()->getName(),
                $targetHonorCount,
            ));
            $raid->setIsActive(false);
            $raid->setIsSuccessful(true);
            $honorPerSupporter = ceil($targetHonorCount / ($supporterCount + 1));
            foreach ($raid->getSupporters() as $supporter) {
                // add honor to supporters
                $this->manager->persist(HonorFactory::create($message->getChat(), $raid->getTarget(), $supporter, $honorPerSupporter));
            }
            // add honor to leader
            $this->manager->persist(HonorFactory::create($message->getChat(), $raid->getTarget(), $raid->getLeader(), $honorPerSupporter));
            // remove honor from target
            $this->manager->persist(HonorFactory::create($message->getChat(), $raid->getTarget(), $raid->getTarget(), -$targetHonorCount));
        } else {
            $totalHonor = $this->honorRepository->getHonorCount($raid->getLeader(), $message->getChat());
            foreach ($raid->getSupporters() as $supporter) {
                $currentSupporterHonor = ceil(abs($this->honorRepository->getHonorCount($supporter, $message->getChat())) / 2);
                $totalHonor += $currentSupporterHonor;
                $this->manager->persist(HonorFactory::create($message->getChat(), $raid->getTarget(), $supporter, -$currentSupporterHonor));
            }
            $raid->setIsActive(false);
            $raid->setIsSuccessful(false);
            $honorPerDefender = ceil($totalHonor / ($defenderCount + 1));
            $this->telegramService->sendText($message->getChat()->getChatId(), sprintf('
                raid against %s failed! the raiders lost a total of %s honor and it will be equally distributed among 
                the defenders and the target. Each defender will receive %s honor. The leader lost all of their honor. The 
                supporters lost half of their honor.
                ',
                $raid->getTarget()->getName(),
                $totalHonor,
                $honorPerDefender,
            ));
            foreach ($raid->getDefenders() as $defender) {
                // add honor to defenders
                $this->manager->persist(HonorFactory::create($message->getChat(), $raid->getLeader(), $defender, $honorPerDefender));
            }
            // add honor to target
            $this->manager->persist(HonorFactory::create($message->getChat(), $raid->getLeader(), $raid->getTarget(), $honorPerDefender));
        }
        $this->manager->flush();
        $this->showLeaderboard($message, false);
        $this->telegramService->sendText($message->getChat()->getChatId(), 'Thank you for participating in RAID: Shadow Legends! (Beta)');
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