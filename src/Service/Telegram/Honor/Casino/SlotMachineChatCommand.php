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

    private const PRICE = 10_000;

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
        return preg_match('/^!slot\s*(?<amount>\d+)?$/', $message->getMessage(), $matches) === 1;
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
        $runs = 1;
        if (isset($matches['amount'])) {
            $amount = NumberFormat::getIntValue($matches['amount']);
            if ($amount > 5) {
                $this->telegramService->replyTo($message, 'you can only run the slot machine 5 times at once');
                return;
            }
            if ($amount > 0) {
                $runs = $amount;
            }
        }
        $cost = $runs * self::PRICE;
        if ($cost > $currentHonor) {
            $this->telegramService->replyTo($message, sprintf('you need %s Ehre to run the slot machine %s times', NumberFormat::format($cost), $runs));
            return;
        }
        $losses = 0;
        $result = [];
        for ($i = 0; $i < $runs; $i++) {
            $result = $this->run();
            if ($result === [7,7,7]) {
                $amount = $jackpot->getAmount();
                $this->honorService->addHonor($message->getChat(), $message->getUser(), $amount);
                $text = <<<TEXT
                🎰 777 🎰
                
                JACKPOT
                after %s tries
                you win %s Ehre
                TEXT;
                $totalTries = $losses + 1;
                $this->honorService->removeHonor($message->getChat(), $message->getUser(), $totalTries * self::PRICE);
                $this->telegramService->replyTo($message, sprintf($text, $totalTries, NumberFormat::format($amount)));
                $jackpot->setAmount(0);
                $this->manager->flush();
                return;
            } else {
                $losses++;
                $jackpot->setAmount($jackpot->getAmount() + self::PRICE);
            }
        }
        $this->honorService->removeHonor($message->getChat(), $message->getUser(), $losses * self::PRICE);
        $this->manager->flush();
        if ($runs === 1) {
            $text = <<<TEXT
            🎰 %s 🎰
            
            you lose
            new jackpot: %s Ehre
            TEXT;
            $this->telegramService->replyTo($message, sprintf($text, implode(' ', $result), NumberFormat::format($jackpot->getAmount())));
        } else {
            $text = <<<TEXT
            you lost %s times in a row (%s Ehre)
            new jackpot: %s Ehre
            TEXT;
            $this->telegramService->replyTo($message, sprintf($text, $losses, NumberFormat::format($losses * self::PRICE), NumberFormat::format($jackpot->getAmount())));
        }
    }

    /**
     * @return array<int, int|string>
     */
    private function run(): array
    {
        $options = ['🍒', '🍋', '🍊', '🍇', '🍉', '🍓', '🍍', '🍌', 7, '🍎'];
        return [
            Random::arrayElement($options),
            Random::arrayElement($options),
            Random::arrayElement($options),
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