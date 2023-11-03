<?php

namespace App\Command\Honor;

use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\HonorMillions\Draw\DrawFactory;
use App\Repository\DrawRepository;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use App\Utils\Random;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('honor:millions:draw')]
class HonorMillionsDrawCommand extends Command
{

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly DrawRepository $drawRepository,
        private readonly TelegramService $telegramService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->drawRepository->getDrawsByDate(new \DateTime()) as $draw) {
            $number = $draw->getWinningNumber() ?? Random::getNumber(100);
            $draw->setWinningNumber($number);
            $winners = $draw->getWinners();
            if ($winners->count() === 0) {
                $this->telegramService->sendText(
                    $draw->getChat()->getChatId(),
                    sprintf('[no winner] Ehre Millions winning number: %d', $number),
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
                        sprintf(
                            '[@%s] Ehre Millions winning number <strong>%d</strong> [WIN %s Ehre]',
                            $winner->getUser()->getName() ?? $winner->getUser()->getFirstName(),
                            $number,
                            NumberFormat::format($amountPerWinner),
                        ),
                        $draw->getTelegramThreadId(),
                        parseMode: 'HTML',
                    );
                    $this->manager->persist(HonorFactory::create($draw->getChat(), null, $winner->getUser(), $amountPerWinner));
                }
                $nextDraw = DrawFactory::create($draw->getChat(), new \DateTime('+1 day'), $draw->getTelegramThreadId());
                $nextDraw->setChat($draw->getChat());
                $nextDraw->setPreviousDraw(null);
                $nextDraw->setPreviousJackpot(1_000_000);
            }
            $this->manager->persist($nextDraw);
            $this->manager->flush();
        }
        return Command::SUCCESS;
    }

}
