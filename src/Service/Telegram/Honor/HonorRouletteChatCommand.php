<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Repository\HonorRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class HonorRouletteChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private HonorRepository $honorRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!roulette (?<amount>\d+) (?<bet>(red|black|1-18|19-36|1-12|13-24|25-36|[0-9]|[1-2][0-9]|3[0-6]))$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $initialAmount = (int) $matches['amount'];
        $bet = $matches['bet'];
        $currentHonor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        if ($currentHonor < $initialAmount) {
            $this->telegramService->replyTo($message, 'not enough ehre');
        } else {
            $number = random_int(0, 36);
            $color = $number === 0 ? '🟢' : ($number % 2 === 0 ? '🔴' : '⚫️');
            $amount = match ($bet) {
                'red' => $number % 2 === 0 && $number !== 0 ? $initialAmount : -$initialAmount,
                'black' => $number % 2 === 1 ? $initialAmount : -$initialAmount,
                '1-12' => $number >= 1 && $number <= 12 ? ($initialAmount * 3) - $initialAmount : -$initialAmount,
                '13-24' => $number >= 13 && $number <= 24 ?  ($initialAmount * 3) - $initialAmount : -$initialAmount,
                '25-36' => $number >= 25 && $number <= 36 ?  ($initialAmount * 3) - $initialAmount : -$initialAmount,
                '1-18' => $number >= 1 && $number <= 18 ? $initialAmount : -$initialAmount,
                '19-36' => $number >= 19 && $number <= 36 ? $initialAmount : -$initialAmount,
                default => $number === (int)$bet ? ($initialAmount * 36) - $initialAmount : -$initialAmount,
            };
            $this->telegramService->replyTo($message, sprintf(
                'the number is %d (%s). You have %s %d ehre',
                $number,
                $color,
                $amount > 0 ? 'won' : 'lost',
                abs($amount))
            );
            $this->manager->persist(HonorFactory::create($message->getChat(), $message->getUser(), $message->getUser(), $amount));
            $this->manager->flush();
        }
    }

}