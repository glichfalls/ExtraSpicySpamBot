<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\DrawRepository;
use App\Service\Collectable\CollectableService;
use App\Service\Collectable\EffectTypes;
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
        return preg_match('/^!(gamble|g)\s(?<count>\d+|max)[km]?$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $currentHonor = $this->honorService->getCurrentHonorAmount($message->getChat(), $message->getUser());
        if ($matches['count'] === 'max') {
            $count = $currentHonor;
        } else {
            if (1 == 2 && NumberFormat::isAbbreviatedNumber($matches['count'])) {
                $count = NumberFormat::unabbreviateNumber($matches['count']);
                if ($count === null) {
                    $this->telegramService->replyTo($message, 'invalid number');
                    return;
                }
            }
            $count = (int) $matches['count'];
        }
        $this->logger->error(sprintf('GAMBLE %s honor', $count));
        if ($count < 0) {
            $this->logger->error('GAMBLE failed, negative honor');
            $this->telegramService->replyTo($message, 'you cannot gamble negative Ehre');
            return;
        }
        if ($currentHonor < $count) {
            $this->logger->error('GAMBLE failed, not enough honor');
            $this->telegramService->replyTo($message, 'not enough Ehre');
        } else {
            $this->logger->error(sprintf('GAMBLE %s start', $message->getUser()->getName()));
            $win = $this->gamble($message->getUser(), $message->getChat());
            if ($win) {
                $this->logger->error(sprintf('GAMBLE %s won %s honor', $message->getUser()->getName(), $count));
                $this->honorService->addHonor($message->getChat(), $message->getUser(), $count);
                $this->manager->flush();
                $this->telegramService->replyTo($message, sprintf('you have won %s Ehre', NumberFormat::format($count)));
            } else {
                $this->logger->error(sprintf('GAMBLE %s lost %s honor', $message->getUser()->getName(), $count));
                $draw = $this->drawRepository->getActiveDrawByChat($message->getChat());
                $draw?->setGamblingLosses($draw->getGamblingLosses() + $count);
                $this->honorService->removeHonor($message->getChat(), $message->getUser(), -$count);
                $this->manager->flush();
                $this->telegramService->replyTo($message, sprintf('you have lost %s Ehre', NumberFormat::format($count)));
            }
            $this->logger->error('GAMBLE end');
        }
    }

    private function gamble(User $user, Chat $chat): bool
    {
        try {
            $effects = $this->collectableService->getEffectsByUserAndType($user, $chat, [
                EffectTypes::GAMBLE_LUCK,
                EffectTypes::LUCK,
            ]);
            $this->logger->debug(sprintf('gamble luck effects: %s', $effects->count()));
            $chance = $effects->apply(50);
            if ($chance > 50) {
                $buff = $chance - 50;
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf('luck buff: %s%%', $buff),
                );
            }
            if ($chance < 50) {
                $debuff = 50 - $chance;
                $this->telegramService->sendText(
                    $chat->getChatId(),
                    sprintf('luck debuff: %s%%', $debuff),
                );
            }
            $this->logger->debug(sprintf('gamble chance: %s', $chance));
            return Random::getPercentChance((int) min($chance, 100));
        } catch (\Error $exception) {
            $this->logger->error('failed to apply gamble luck effects', [
                'exception' => $exception,
            ]);
            return Random::getPercentChance(50);
        }
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
