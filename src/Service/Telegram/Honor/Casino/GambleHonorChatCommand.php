<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\DrawRepository;
use App\Service\Collectable\CollectableService;
use App\Service\Collectable\EffectType;
use App\Service\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use App\Utils\Random;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class GambleHonorChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly HonorService $honorService,
        private readonly DrawRepository $drawRepository,
        private readonly CollectableService $collectableService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(gamble|g)\s(?<amount>\d+|max)(?<abbr>[km])?$/', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $currentHonor = $this->honorService->getCurrentHonorAmount($message->getChat(), $message->getUser());
        if ($matches['amount'] === 'max') {
            $amount = $currentHonor;
        } else {
            if (array_key_exists('abbr', $matches) && NumberFormat::isAbbreviatedNumber($matches['count'])) {
                $amount = NumberFormat::unabbreviateNumber(sprintf('%s%s', $matches['amount'], $matches['abbr']));
                if ($amount === null) {
                    $this->telegramService->replyTo($message, 'invalid number');
                    return;
                }
            }
            $amount = (int) $matches['amount'];
        }
        $this->logger->info(sprintf('GAMBLE %s honor', $amount));
        if ($amount < 0) {
            $this->logger->info('GAMBLE failed, negative honor');
            $this->telegramService->replyTo($message, 'you cannot gamble negative Ehre');
            return;
        }
        if ($currentHonor < $amount) {
            $this->logger->info('GAMBLE failed, not enough honor');
            $this->telegramService->replyTo($message, 'not enough Ehre');
        } else {
            $this->logger->info(sprintf('GAMBLE %s start', $message->getUser()->getName()));
            $chance = $this->getChance($message->getUser(), $message->getChat());
            if ($chance > 50) {
                $buff = ($chance - 50) * 2;
            } else {
                $buff = (50 - $chance) * 2;
            }
            if ($this->gamble($chance)) {
                $this->logger->info(sprintf('GAMBLE %s won %s honor', $message->getUser()->getName(), $amount));
                $this->honorService->addHonor($message->getChat(), $message->getUser(), $amount);
                $this->manager->flush();
                $this->telegramService->replyTo(
                    $message,
                    sprintf('you have won %s Ehre (effect: %s%%)', NumberFormat::format($amount), $buff)
                );
            } else {
                $this->logger->info(sprintf('GAMBLE %s lost %s honor', $message->getUser()->getName(), $amount));
                $draw = $this->drawRepository->getActiveDrawByChat($message->getChat());
                $draw?->setGamblingLosses($draw->getGamblingLosses() + $amount);
                $this->honorService->removeHonor($message->getChat(), $message->getUser(), $amount);
                $this->manager->flush();
                $this->telegramService->replyTo(
                    $message,
                    sprintf('you have lost %s Ehre (effect: %s%%)', NumberFormat::format($amount), $buff),
                );
            }
        }
    }

    private function getChance(User $user, Chat $chat): int
    {
        try {
            $effects = $this->collectableService->getEffectsByUserAndType($user, $chat, [
                EffectType::GAMBLE_LUCK,
                EffectType::LUCK,
            ]);
            $chance = $effects->apply(50);
            $this->logger->info(sprintf('gamble luck effects: %s', $effects->count()));
            return $chance;
        } catch (\Exception $exception) {
            $this->logger->info('failed to apply gamble luck effects', [
                'exception' => $exception,
            ]);
            return 50;
        }
    }

    private function gamble(int $chance): bool
    {
        return Random::getPercentChance((int) min($chance, 100));
    }

    public function getSyntax(): string
    {
        return '!gamble <count> | !g <count> | !gamble max | !g max';
    }

    public function getDescription(): string
    {
        return 'gamble Ehre (50% win chance)';
    }

}
