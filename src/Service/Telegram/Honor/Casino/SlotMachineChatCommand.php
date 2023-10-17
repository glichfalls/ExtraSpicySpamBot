<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Message\Message;
use App\Repository\DrawRepository;
use App\Service\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use App\Utils\Random;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class SlotMachineChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly HonorService $honorService,
        private readonly DrawRepository $drawRepository,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!(slot)\s(?<amount>\d+|max)(?<abbr>[km])?$/', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $currentHonor = $this->honorService->getCurrentHonorAmount($message->getChat(), $message->getUser());
        if ($matches['amount'] === 'max') {
            $amount = $currentHonor;
        } else {
            $amount = NumberFormat::getIntValue($matches['amount'], $matches['abbr'] ?? null);
        }
        if ($amount < 0) {
            $this->logger->info('GAMBLE failed, negative honor');
            $this->telegramService->replyTo($message, 'you cannot gamble negative Ehre');
            return;
        }
        if ($currentHonor < $amount) {
            $this->logger->info('GAMBLE failed, not enough honor');
            $this->telegramService->replyTo($message, 'not enough Ehre');
            return;
        }
        $result = $this->run();
        $multiplier = $this->getMultiplier($result);
        $resultAmount = $amount * $multiplier;
        if ($resultAmount > 0) {
            $this->honorService->addHonor($message->getChat(), $message->getUser(), $resultAmount);
        } else {
            $this->honorService->removeHonor($message->getChat(), $message->getUser(), $amount);
            $draw = $this->drawRepository->getActiveDrawByChat($message->getChat());
            $draw?->setGamblingLosses($draw->getGamblingLosses() + $amount);
        }
        $text = <<<TEXT
        🎰 %s 🎰
        you win %s honor
        TEXT;
        $this->manager->flush();
        $this->telegramService->replyTo($message, sprintf($text, implode(' ', $result), $resultAmount));
    }

    /**
     * @return array<int, int>
     */
    private function run(): array
    {
        return [
            Random::getNumber(9, 0),
            Random::getNumber(9, 0),
            Random::getNumber(9, 0),
        ];
    }

    private function getMultiplier(array $numbers): int
    {
        return match ($numbers) {
            [7, 7, 7] => 1000,
            default => 0,
        };
    }

    public function getDescription(): string
    {
        return 'play the slot machine';
    }

    public function getSyntax(): string
    {
        return '!slot [amount]';
    }

}