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
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->drawRepository->getDrawsByDate(new \DateTime()) as $draw) {
            // use the winning number from the draw if it exists, otherwise generate a random number
            // this is useful to re-run the command if something went wrong without changing the winning number
            $number = $draw->getWinningNumber() ?? random_int(1, 100);
            $this->telegramService->sendText(
                $draw->getChat()->getChatId(),
                sprintf('The ehre Millions draw has been made! The winning number is %d', $number),
                $draw->getTelegramThreadId(),
            );
            $draw->setWinningNumber($number);
            $winners = $draw->getWinners();
            if ($winners->count() === 0) {
                $this->telegramService->sendText(
                    $draw->getChat()->getChatId(),
                    'Unfortunately, there are no winners this time.',
                    $draw->getTelegramThreadId(),
                );
                $nextDraw = DrawFactory::create($draw->getChat(), new \DateTime('+1 day'), $draw->getTelegramThreadId());
                $nextDraw->setPreviousDraw($draw);
                $nextDraw->setPreviousJackpot($draw->getJackpot());
            } else {
                $jackpot = $draw->getJackpot();
                $amountPerWinner = (int) ceil(abs($jackpot) / $winners->count());
                foreach ($winners as $winner) {
                    $this->telegramService->sendText(
                        $draw->getChat()->getChatId(),
                        sprintf('Congratulations %s, you have won %d ehre!', $winner->getUser()->getName(), $amountPerWinner),
                        $draw->getTelegramThreadId(),
                    );
                    $this->manager->persist(HonorFactory::create($draw->getChat(), null, $winner->getUser(), $amountPerWinner));
                }
                $nextDraw = DrawFactory::create($draw->getChat(), new \DateTime('+1 day'), $draw->getTelegramThreadId());
                $nextDraw->setChat($draw->getChat());
                $nextDraw->setPreviousDraw(null);
                $nextDraw->setPreviousJackpot(100_000);
            }
            $this->manager->persist($nextDraw);
            $this->manager->flush();
            $this->telegramService->sendText(
                $draw->getChat()->getChatId(),
                sprintf('The next draw will be on %s', $nextDraw->getDate()->format('d.m.Y')),
                $draw->getTelegramThreadId(),
            );
        }
        return Command::SUCCESS;
    }

}
