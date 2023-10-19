<?php

namespace App\Service\Telegram\Raid;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\Raid\Raid;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Service\Collectable\EffectType;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Utils\NumberFormat;
use App\Utils\Random;
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
            if ($this->isSuccessful($raid)) {
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
                $this->fail($raid);
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
                'Raid finished',
                false,
            );
            $this->telegramService->deleteMessage(
                $update->getCallbackQuery()->getMessage()->getChat()->getId(),
                $update->getCallbackQuery()->getMessage()->getMessageId(),
            );
        } catch (\RuntimeException $exception) {
            $this->logger->info($exception->getMessage());
            $this->telegramService->answerCallbackQuery(
                $update->getCallbackQuery(),
                $exception->getMessage(),
                true,
            );
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
            if ($this->isSuccessful($raid)) {
                $this->success($raid);
                $this->telegramService->replyTo(
                    $message,
                    $this->translator->trans('telegram.raid.raidSuccessful', [
                        'target' => $raid->getTarget()->getName(),
                        'honorCount' => NumberFormat::format($raid->getAmount()),
                    ]),
                );
            } else {
                $this->fail($raid);
                $this->telegramService->replyTo(
                    $message,
                    $this->translator->trans('telegram.raid.raidFailed', [
                        'target' => $raid->getTarget()->getName() ?? $raid->getTarget()->getFirstName(),
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

    private function isSuccessful(Raid $raid): bool
    {
        $leaderEffects = $this->collectableService->getEffectsByUserAndType($raid->getLeader(), $raid->getChat(), [
            EffectType::OFFENSIVE_RAID_SUCCESS,
        ]);
        $successChance = $leaderEffects->apply(50);
        $targetEffects = $this->collectableService->getEffectsByUserAndType($raid->getTarget(), $raid->getChat(), [
            EffectType::DEFENSIVE_RAID_SUCCESS,
        ]);
        $successChance = $targetEffects->apply($successChance);
        if ($successChance <= 0) {
            return false;
        }
        if ($successChance >= 100) {
            return true;
        }
        return Random::getPercentChance($successChance);
    }

    private function success(Raid $raid): void
    {
        $raid->setIsActive(false);
        $raid->setIsSuccessful(true);
        $amount = $raid->getAmount();
        for ($i = 0; $i < $raid->getSupporters()->count(); $i++) {
            $amount *= 1.2; // 20% more for each supporter
        }
        $additionalAmount = $amount - $raid->getAmount();
        for ($i = 0; $i < $raid->getDefenders()->count(); $i++) {
            $amount *= 0.75; // 25% less for each defender
        }
        $reducedAmount = $raid->getAmount() - $amount;
        $amount = (int) round($amount);
        $this->logger->info(sprintf(
            'raid successful total %s | +%s Ehre (%s sup), -%s Ehre (%s def)',
            NumberFormat::format($amount),
            NumberFormat::format($additionalAmount),
            $raid->getSupporters()->count(),
            NumberFormat::format($reducedAmount),
            $raid->getDefenders()->count(),
        ));
        $numberOfSupporters = $raid->getSupporters()->count() + 1;
        $honorPerSupporter = (int) ceil($amount / $numberOfSupporters);
        // add honor to leader
        $this->manager->persist(HonorFactory::create($raid->getChat(), null, $raid->getLeader(), $honorPerSupporter));
        // add honor to supporters
        foreach ($raid->getSupporters() as $supporter) {
            $this->manager->persist(HonorFactory::create($raid->getChat(), null, $supporter, $honorPerSupporter));
        }
        // remove honor from target
        $this->manager->persist(HonorFactory::create($raid->getChat(), null, $raid->getTarget(), -$raid->getAmount()));
        $this->manager->flush();
    }

    private function fail(Raid $raid): void
    {
        $raid->setIsActive(false);
        $raid->setIsSuccessful(false);
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