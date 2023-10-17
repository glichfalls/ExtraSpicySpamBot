<?php

namespace App\Service\Telegram\Honor\Casino;

use App\Entity\Message\Message;
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

    private const PRICE = 1000;

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly HonorService $honorService,

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
        if ($currentHonor < self::PRICE) {
            $this->telegramService->replyTo($message, 'not enough Ehre');
            return;
        }
        $jackpot = $this->honorService->getSlotMachineJackpot($message->getChat());
        $this->honorService->removeHonor($message->getChat(), $message->getUser(), self::PRICE);
        $result = $this->run();
        if ($result === [7,7,7]) {
            $amount = $jackpot->getAmount();
            $this->honorService->addHonor($message->getChat(), $message->getUser(), $amount);
            $text = <<<TEXT
            🎰 %s 🎰
            
            JACKPOT
            you win %s Ehre
            TEXT;
            $this->telegramService->replyTo($message, sprintf($text, implode(' ', $result), NumberFormat::format($amount)));
            $jackpot->setAmount(0);
        } else {
            $jackpot->setAmount($jackpot->getAmount() + self::PRICE);
            $text = <<<TEXT
            🎰 %s 🎰
            
            you lose
            new jackpot: %s Ehre
            TEXT;
            $this->telegramService->replyTo($message, sprintf($text, implode(' ', $result), NumberFormat::format($jackpot->getAmount())));
        }
        $this->manager->flush();
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

    public function getDescription(): string
    {
        return 'play the slot machine';
    }

    public function getSyntax(): string
    {
        return '!slot [amount]';
    }

}