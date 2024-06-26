<?php declare(strict_types=1);

namespace App\Service\Telegram\Honor\Millions;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\HonorMillions\Draw\Draw;
use App\Entity\Honor\HonorMillions\Ticket\Ticket;
use App\Entity\Honor\HonorMillions\Ticket\TicketFactory;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Repository\DrawRepository;
use App\Repository\HonorRepository;
use App\Service\Honor\HonorService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
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
        private readonly DrawRepository $drawRepository,
        private readonly HonorRepository $honorRepository,
        private readonly HonorService $honorService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!ticket\s?(?<numbers>[\d ,]*)/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $draw = $this->drawRepository->getActiveDrawByChat($message->getChat());

        if ($draw === null) {
            $this->telegramService->replyTo($message, 'there is no draw for this chat');
            return;
        }

        try {
            $numbers = $this->getNumbers($matches);
        } catch (\InvalidArgumentException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
            return;
        }

        if (count($numbers) === 0) {
            $this->showTickets($message, $draw);
            return;
        }

        $ticket = $draw->getTicketByUser($message->getUser());

        if ($ticket === null) {
            $ticket = TicketFactory::create($message->getUser(), $draw);
            $draw->getTickets()->add($ticket);
            $this->manager->persist($ticket);
        }

        if (count(array_diff($numbers, $ticket->getNumbers())) !== count($numbers)) {
            $this->telegramService->replyTo($message, sprintf(
                'you can only buy tickets with numbers you already have. you have %s',
                implode(', ', $ticket->getNumbers()),
            ));
            return;
        }

        try {
            $total = Honor::currency(0);
            $honor = $this->honorService->getCurrentHonorAmount($message->getChat(), $message->getUser());
            foreach ($numbers as $number) {
                $price = $this->buyTicket($message->getChat(), $message->getUser(), $ticket, $honor, $number);
                $total = $total->add($price);
            }
            $this->manager->flush();
            sort($numbers);
            $this->telegramService->replyTo($message, sprintf(
                'you bought %d tickets (%s) for %s ehre',
                count($numbers),
                implode(', ', $numbers),
                NumberFormat::money($total),
            ));
        } catch (\InvalidArgumentException $exception) {
            $this->manager->clear();
            $this->telegramService->replyTo($message, $exception->getMessage());
            return;
        }
    }

    private function getNumbers(array $matches): array
    {
        $numbers = [];
        foreach (explode(',', $matches['numbers']) as $number) {
            if (!is_numeric($number)) {
                continue;
            }
            $number = (int) trim($number);
            if ($number < 1 || $number > 100) {
                throw new \InvalidArgumentException(sprintf('%d is not in range. number must be between 1 and 100', $number));
            }
            $numbers[] = $number;
        }
        return $numbers;
    }

    private function showTickets(Message $message, Draw $draw): void
    {
        $ticket = $draw->getTicketByUser($message->getUser());
        if ($ticket === null) {
            $this->telegramService->replyTo($message, 'you have no tickets');
            return;
        }
        $numbers = $ticket->getNumbers();
        sort($numbers);
        $this->telegramService->replyTo($message, sprintf(
            'you have %d tickets: %s',
            count($ticket->getNumbers()),
            implode(', ', $numbers),
        ));
    }

    private function buyTicket(Chat $chat, User $user, Ticket $ticket, Money $currentHonor, int $number): Money
    {
        $ticketPrice = $this->getTicketPrice($ticket);
        if ($ticketPrice->greaterThan(Honor::currency(0))) {
            if ($ticketPrice->greaterThan($currentHonor)) {
                throw new \InvalidArgumentException(sprintf(
                    'you need %s ehre to buy a ticket, but you only have %s ehre',
                    NumberFormat::money($ticketPrice),
                    NumberFormat::money($currentHonor),
                ));
            }
            $this->honorService->removeHonor($chat, $user, $ticketPrice);
        }
        $ticket->addNumber($number);
        return $ticketPrice;
    }

    private function getTicketPrice(Ticket $ticket): Money
    {
        $numberOfTickets = count($ticket->getNumbers());
        if ($numberOfTickets === 0) {
            return Honor::currency(0);
        }
        return Honor::currency(10 ** ++$numberOfTickets);
    }

    public function getSyntax(): string
    {
        return '!ticket <strong>number</strong>';
    }

    public function getDescription(): string
    {
        return 'buy a ticket for the ehre millions draw, first ticket is free, next ticket costs 100 ehre, then 1k, then 10k, etc.';
    }

}
