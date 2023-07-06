<?php

namespace App\Service\Telegram\Honor;

use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\HonorMillions\Ticket\Ticket;
use App\Entity\Honor\HonorMillions\Ticket\TicketFactory;
use App\Entity\Message\Message;
use App\Repository\DrawRepository;
use App\Repository\HonorRepository;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class BuyHonorMillionsTicketChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private DrawRepository $drawRepository,
        private HonorRepository $honorRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!ticket (?<number>\d+)/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $draw = $this->drawRepository->getActiveDrawByChat($message->getChat());
        if ($draw === null) {
            $this->telegramService->replyTo($message, 'there is no draw for this chat');
            return;
        }
        $number = (int) $matches['number'];
        if ($number < 1 || $number > 100) {
            $this->telegramService->replyTo($message, sprintf('%d is not in range. number must be between 1 and 100', $number));
            return;
        }
        $ticket = $draw->getTicketByUser($message->getUser());
        if ($ticket === null) {
            $ticket = TicketFactory::create($message->getUser(), $draw);
            $draw->getTickets()->add($ticket);
            $this->manager->persist($ticket);
        }
        if (in_array($number, $ticket->getNumbers())) {
            $this->telegramService->replyTo($message, sprintf('you already have the number %d', $number));
            return;
        }
        $ticketPrice = $this->getTicketPrice($ticket);
        if ($ticketPrice !== 0) {
            $honor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
            if ($ticketPrice > $honor) {
                $this->telegramService->replyTo($message, sprintf(
                    'you need %d ehre to buy a ticket, but you only have %d ehre',
                    $ticketPrice,
                    $honor,
                ));
                return;
            }
            $this->manager->persist(HonorFactory::create($message->getChat(), null, $message->getUser(), -$ticketPrice));
        }
        $ticket->addNumber($number);
        $this->manager->flush();
        $this->telegramService->replyTo($message, sprintf(
            'ticket bought with number %d for %d ehre. You now have %d tickets. Your next ticket will cost %d ehre.',
            $number,
            $ticketPrice,
            count($ticket->getNumbers()),
            $this->getTicketPrice($ticket),
        ));
    }

    private function getTicketPrice(Ticket $ticket): int
    {
        $numberOfTickets = count($ticket->getNumbers());
        if ($numberOfTickets === 0) {
            return 0;
        }
        return pow(10, $numberOfTickets + 1);
    }

    public function getSyntax(): string
    {
        return '!ticket <number>';
    }

    public function getDescription(): string
    {
        return 'buy a ticket for the ehre millions draw, first ticket is free, next ticket costs 100 ehre, then 1k, then 10k, etc.';
    }

}