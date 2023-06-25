<?php

namespace App\Command\Honor;

use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\HonorMillions\Draw\DrawFactory;
use App\Repository\DrawRepository;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('honor:millions:draw')]
class HonorMillionsDrawCommand extends Command
{

    public function __construct(
        private EntityManagerInterface $manager,
        private DrawRepository $drawRepository,
        private TelegramService $telegramService,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $draws = $this->drawRepository->getDrawsByDate(new \DateTime());
        foreach ($draws as $draw) {
            $number = random_int(1, 100);
            $this->telegramService->sendText(
                $draw->getChat()->getChatId(),
                sprintf('The Honor Millions draw has been made! The winning number is %d', $number)
            );
            $winners = $draw->getTickets()->filter(fn($ticket) => $ticket->getNumber() === $number);
            if ($winners->count() === 0) {
                $this->telegramService->sendText(
                    $draw->getChat()->getChatId(),
                    'Unfortunately, there are no winners this time.'
                );
                $nextDraw = DrawFactory::create($draw->getChat(), new \DateTime('+1 day'));
                $nextDraw->setPreviousDraw($draw);
                $nextDraw->setPreviousJackpot($draw->getJackpot());
            } else {
                $jackpot = $draw->getJackpot();
                $amountPerWinner = ceil($jackpot / $winners->count());
                foreach ($winners as $winner) {
                    $this->telegramService->sendText(
                        $draw->getChat()->getChatId(),
                        sprintf('Congratulations %s, you have won %d ehre!', $winner->getUser()->getUsername(), $amountPerWinner)
                    );
                    $this->manager->persist(HonorFactory::create($draw->getChat(), null, $winner->getUser(), $amountPerWinner));
                }
                $nextDraw = DrawFactory::create($draw->getChat(), new \DateTime('+1 day'));
                $nextDraw->setChat($draw->getChat());
                $nextDraw->setPreviousDraw(null);
                $nextDraw->setPreviousJackpot(0);
            }
            $this->manager->persist($nextDraw);
            $this->manager->flush();
            $this->telegramService->sendText(
                $draw->getChat()->getChatId(),
                sprintf('The next draw will be on %s', $nextDraw->getDate()->format('d.m.Y'))
            );
        }
        return Command::SUCCESS;
    }

}