<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\DrawRepository;
use App\Repository\HonorRepository;
use App\Service\Collectable\CollectableService;
use App\Service\Collectable\EffectTypes;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Strategy\Effect\EffectStrategyFactory;
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
        private HonorRepository $honorRepository,
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
        $currentHonor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
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
        if ($currentHonor < $count) {
            $this->telegramService->replyTo($message, 'not enough Ehre');
        } else {
            if ($this->gamble($message->getUser(), $message->getChat())) {
                $this->manager->persist(HonorFactory::create($message->getChat(), $message->getUser(), $message->getUser(), $count));
                $this->manager->flush();
                $this->telegramService->replyTo($message, sprintf('you have won %s Ehre', NumberFormat::format($count)));
            } else {
                $draw = $this->drawRepository->getActiveDrawByChat($message->getChat());
                $draw?->setGamblingLosses($draw->getGamblingLosses() + $count);
                $this->manager->persist(HonorFactory::create($message->getChat(), $message->getUser(), $message->getUser(), -$count));
                $this->manager->flush();
                $this->telegramService->replyTo($message, sprintf('you have lost %s Ehre', NumberFormat::format($count)));
            }
        }
    }

    private function gamble(User $user, Chat $chat): bool
    {
        $chance = 50;
        $effects = $this->collectableService->getEffectsByUserAndType($user, $chat, EffectTypes::GAMBLE_LUCK);
        foreach ($effects as $effect) {
            $chance = $effect->apply($chance);
        }
        return Random::getPercentChance($chance);
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
