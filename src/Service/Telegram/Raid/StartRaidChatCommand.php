<?php

namespace App\Service\Telegram\Raid;

use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Repository\HonorRepository;
use App\Repository\RaidRepository;
use App\Service\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class StartRaidChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface  $manager,
        TranslatorInterface     $translator,
        LoggerInterface         $logger,
        TelegramService         $telegramService,
        private RaidRepository  $raidRepository,
        private HonorRepository $honorRepository,
        private HonorService    $honorService,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!start raid$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $raid = $this->raidRepository->getActiveRaid($message->getChat());
        if ($raid === null) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.noActiveRaid'));
            return;
        }
        if ($raid->getLeader()->getTelegramUserId() !== $message->getUser()->getTelegramUserId()) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.noLeaderError'));
            return;
        }

        $supporterCount = $raid->getSupporters()->count();
        $defenderCount = $raid->getDefenders()->count();

        if ($supporterCount + $defenderCount === 0) {
            $this->telegramService->replyTo($message, $this->translator->trans('telegram.raid.noSupportersOrDefendersError'));
            return;
        }

        if (random_int(1, 10) <= 6) {
            $this->telegramService->replyTo(
                $message,
                $this->translator->trans('telegram.raid.raidSuccessful', [
                    'target' => $raid->getTarget()->getName(),
                    'honorCount' => $raid->getAmount(),
                ]),
            );
            $raid->setIsActive(false);
            $raid->setIsSuccessful(true);
            $honorPerSupporter = $raid->getAmount() / ($supporterCount + 1);
            // add honor to leader
            $this->manager->persist(HonorFactory::create($message->getChat(), null, $raid->getLeader(), $honorPerSupporter));
            // add honor to supporters
            foreach ($raid->getSupporters() as $supporter) {
                $this->manager->persist(HonorFactory::create($message->getChat(), null, $supporter, $honorPerSupporter));
            }
            // remove honor from target
            $this->manager->persist(HonorFactory::create($message->getChat(), null, $raid->getTarget(), -$raid->getAmount()));
            // remove honor from defenders
            foreach ($raid->getDefenders() as $defender) {
                $this->manager->persist(HonorFactory::create($message->getChat(), null, $defender, -$honorPerSupporter));
            }
        } else {
            $totalHonor = $this->honorRepository->getHonorCount($raid->getLeader(), $message->getChat()) / 2;
            $this->manager->persist(HonorFactory::create($message->getChat(), null, $raid->getLeader(), -$totalHonor));
            foreach ($raid->getSupporters() as $supporter) {
                $currentSupporterHonor = ceil(abs($this->honorRepository->getHonorCount($supporter, $message->getChat())) / 4);
                $totalHonor += $currentSupporterHonor;
                $this->manager->persist(HonorFactory::create($message->getChat(), null, $supporter, -$currentSupporterHonor));
            }
            $raid->setIsActive(false);
            $raid->setIsSuccessful(false);
            $honorPerDefender = ceil($totalHonor / ($defenderCount + 1));
            $this->telegramService->replyTo(
                $message,
                $this->translator->trans('telegram.raid.raidFailed', [
                    'target' => $raid->getTarget()->getName(),
                    'totalHonor' => $totalHonor,
                    'honorPerDefender' => $honorPerDefender,
                ]),
            );
            foreach ($raid->getDefenders() as $defender) {
                // add honor to defenders
                $this->manager->persist(HonorFactory::create($message->getChat(), null, $defender, $honorPerDefender));
            }
            // add honor to target
            $this->manager->persist(HonorFactory::create($message->getChat(), null, $raid->getTarget(), $honorPerDefender));
        }
        $this->manager->flush();
        $leaderboard = $this->honorService->getLeaderboardByChat($message->getChat());
        $this->telegramService->sendText($message->getChat()->getChatId(), $leaderboard, $message->getTelegramThreadId());
    }

}