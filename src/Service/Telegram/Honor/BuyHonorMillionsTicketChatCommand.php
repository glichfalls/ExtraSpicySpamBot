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
        $draw = $this->drawRepository->getByChatAndDate($message->getChat(), new \DateTime());
        if ($draw === null) {
            $this->telegramService->replyTo($message, 'there is no draw for this chat');
            return;
        }
        if ($draw->getTickets()->filter(fn(Ticket $ticket) => $ticket->getUser()->getId() === $message->getUser()->getId())->count() > 0) {
            $this->telegramService->replyTo($message, 'you already have a ticket');
            return;
        }
        $currentHonor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
        if ($currentHonor < Ticket::TICKET_PRICE) {
            $this->telegramService->replyTo($message, 'you need 100 ehre to buy a ticket');
            return;
        }
        $number = (int) $matches['number'];
        if ($number < 1 || $number > 100) {
            $this->telegramService->replyTo($message, sprintf('%d is not in range. number must be between 1 and 100', $number));
            return;
        }
        $ticket = TicketFactory::create($message->getUser(), $draw, Ticket::TICKET_PRICE);
        $draw->getTickets()->add($ticket);
        $this->manager->persist(HonorFactory::create($message->getChat(), null, $message->getUser(), -Ticket::TICKET_PRICE));
        $this->manager->persist($ticket);
        $this->manager->flush();
        $this->telegramService->replyTo($message, sprintf(
            'ticket bought with number %d for %d ehre. The jackpot is now %d ehre',
            $number,
            Ticket::TICKET_PRICE,
            $draw->getJackpot(),
        ));
    }

}