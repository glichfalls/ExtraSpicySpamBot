<?php

namespace App\Service\Telegram\Raid;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\Raid\Raid;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Telegram\TelegramCallbackQueryListener;
use TelegramBot\Api\Types\Update;

class StartRaidChatCommand extends AbstractRaidChatCommand implements TelegramCallbackQueryListener
{
    public const CALLBACK_KEYWORD = 'raid:start';

    public function getCallbackKeyword(): string
    {
        return self::CALLBACK_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        try {
            $raid = $this->getActiveRaid($chat);
            $this->canStartRaid($raid, $user);
            if ($this->isSuccessful()) {
                $this->success($raid);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    $this->translator->trans('telegram.raid.raidSuccessful', [
                        'target' => $raid->getTarget()->getName(),
                        'honorCount' => $raid->getAmount(),
                    ]),
                    threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                );
            } else {
                $this->failure($raid);
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    $this->translator->trans('telegram.raid.raidFailed', [
                        'target' => $raid->getTarget()->getName(),
                    ]),
                    threadId: $update->getCallbackQuery()->getMessage()->getMessageThreadId(),
                );
            }
            $this->telegramService->answerCallbackQuery(
                $update->getCallbackQuery(),
                'Raid has finished',
                false,
            );
        } catch (\RuntimeException $exception) {
            $this->logger->info($exception->getMessage());
        }
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!start raid$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $raid = $this->getActiveRaid($message->getChat());
            $this->canStartRaid($raid, $message->getUser());
            if ($this->isSuccessful()) {
                $this->success($raid);
                $this->telegramService->replyTo(
                    $message,
                    $this->translator->trans('telegram.raid.raidSuccessful', [
                        'target' => $raid->getTarget()->getName(),
                        'honorCount' => $raid->getAmount(),
                    ]),
                );
            } else {
                $this->failure($raid);
                $this->telegramService->replyTo(
                    $message,
                    $this->translator->trans('telegram.raid.raidFailed', [
                        'target' => $raid->getTarget()->getName(),
                    ]),
                );
            }
        } catch (\RuntimeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        }
    }

    private function canStartRaid(Raid $raid, User $user): void
    {
        if ($raid->getLeader()->getTelegramUserId() !== $user->getTelegramUserId()) {
            throw new \RuntimeException($this->translator->trans('telegram.raid.noLeaderError'));
        }
        $supporterCount = $raid->getSupporters()->count();
        $defenderCount = $raid->getDefenders()->count();
        if ($supporterCount + $defenderCount === 0) {
            throw new \RuntimeException($this->translator->trans('telegram.raid.noSupportersOrDefendersError'));
        }
    }

    private function isSuccessful(): bool
    {
        return random_int(1, 10) <= 6;
    }

    private function success(Raid $raid): void
    {
        $raid->setIsActive(false);
        $raid->setIsSuccessful(true);
        $honorPerSupporter = $raid->getAmount() / ($raid->getSupporters()->count() + 1);
        // add honor to leader
        $this->manager->persist(HonorFactory::create($raid->getChat(), null, $raid->getLeader(), $honorPerSupporter));
        // add honor to supporters
        foreach ($raid->getSupporters() as $supporter) {
            $this->manager->persist(HonorFactory::create($raid->getChat(), null, $supporter, $honorPerSupporter));
        }
        // remove honor from target
        $this->manager->persist(HonorFactory::create($raid->getChat(), null, $raid->getTarget(), -$raid->getAmount()));
        // remove honor from defenders
        foreach ($raid->getDefenders() as $defender) {
            $this->manager->persist(HonorFactory::create($raid->getChat(), null, $defender, -$honorPerSupporter));
        }
        $this->manager->flush();
    }

    private function failure(Raid $raid): void
    {
        $totalHonor = $this->honorRepository->getHonorCount($raid->getLeader(), $raid->getChat()) / 2;
        $this->manager->persist(HonorFactory::create($raid->getChat(), null, $raid->getLeader(), -$totalHonor));
        foreach ($raid->getSupporters() as $supporter) {
            $currentSupporterHonor = ceil(abs($this->honorRepository->getHonorCount($supporter, $raid->getChat())) / 4);
            $totalHonor += $currentSupporterHonor;
            $this->manager->persist(HonorFactory::create($raid->getChat(), null, $supporter, -$currentSupporterHonor));
        }
        $raid->setIsActive(false);
        $raid->setIsSuccessful(false);
        $honorPerDefender = ceil($totalHonor / ($raid->getDefenders()->count() + 1));
        foreach ($raid->getDefenders() as $defender) {
            // add honor to defenders
            $this->manager->persist(HonorFactory::create($raid->getChat(), null, $defender, $honorPerDefender));
        }
        // add honor to target
        $this->manager->persist(HonorFactory::create($raid->getChat(), null, $raid->getTarget(), $honorPerDefender));
        $this->manager->flush();
    }

    public function getSyntax(): string
    {
        return '!start raid';
    }

    public function getDescription(): string
    {
        return 'start the active raid';
    }

}