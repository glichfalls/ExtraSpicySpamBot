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
        return preg_match('/^!roulette (?<amount>\d+) (?<bet>(red|black|36|[1-9]|[1-2][0-9]|3[0-6]|0))$/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $amount = (int) $matches['amount'];
        $bet = $matches['bet'];
        $currentHonor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        if ($currentHonor < $amount) {
            $this->telegramService->replyTo($message, 'not enough ehre');
        } else {
            $number = random_int(0, 36);
            $this->telegramService->replyTo($message, sprintf('the number is %d', $number));
            $amount = match ($bet) {
                'red' => $number % 2 === 0 && $number !== 0 ? $amount * 2 : -$amount,
                'black' => $number % 2 === 1 ? $amount * 2 : -$amount,
                default => $number === (int)$bet ? $amount * 36 : -$amount,
            };
            $this->telegramService->replyTo($message, sprintf('you have %s %d ehre', $amount > 0 ? 'won' : 'lost', abs($amount)));
            $this->manager->persist(HonorFactory::create($message->getChat(), $message->getUser(), $message->getUser(), $amount));
            $this->manager->flush();
        }
    }

}