<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\DrawRepository;
use App\Service\Collectable\CollectableService;
use App\Service\Collectable\EffectTypes;
use App\Service\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\Memory;
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
        private HonorService $honorService,
        private DrawRepository $drawRepository,
        private CollectableService $collectableService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(gamble|g)\s(?<count>\d+|max)[km]?$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        Memory::logMemoryReport($this->logger);
        $this->logger->error(sprintf('GAMBLE memory usage: %s', memory_get_usage(true)));
        $currentHonor = $this->honorService->getCurrentHonorAmount($message->getChat(), $message->getUser());
        $this->logger->error(sprintf('GAMBLE current honor: %s', $currentHonor));
        $this->logger->error(sprintf('GAMBLE memory usage: %s', memory_get_usage(true)));
        if ($matches['count'] === 'max') {
            $count = $currentHonor;
        } else {
            if (NumberFormat::isAbbreviatedNumber($matches['count'])) {
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
            $this->logger->error(sprintf('GAMBLE memory usage: %s', memory_get_usage(true)));
            if ($this->gamble($message->getUser(), $message->getChat())) {
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
            $this->logger->debug('gamble luck effects');
            $effects = $this->collectableService->getEffectsByUserAndType($user, $chat, EffectTypes::GAMBLE_LUCK);
            $this->logger->debug(sprintf('gamble luck effects: %s', $effects->count()));
            $chance = $effects->apply(50);
            $this->logger->debug(sprintf('gamble chance: %s', $chance));
            return Random::getPercentChance(min($chance, 100));
        } catch (\Error $exception) {
            $this->logger->error('failed to apply gamble luck effects', [
                'exception' => $exception,
            ]);
            return false;
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
