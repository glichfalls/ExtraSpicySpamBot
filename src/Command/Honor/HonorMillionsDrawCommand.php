<?php declare(strict_types=1);

namespace App\Command\Honor;

use App\Entity\Honor\Honor;
use App\Entity\Honor\HonorFactory;
use App\Entity\Honor\HonorMillions\Draw\DrawFactory;
use App\Repository\DrawRepository;
use App\Service\Honor\HonorService;
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
        private readonly HonorService $honorService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->drawRepository->getDrawsByDate(new \DateTime()) as $draw) {
            if ($draw->getWinningNumber() !== null) {
                continue;
            }
            $winningNumber = Random::number(100);
            $draw->setWinningNumber($winningNumber);
            $winners = $draw->getWinners();
            if ($winners->count() === 0) {
                $message = <<<MESSAGE
                Ehre Millions
                The number is <strong>%s</strong>

                No winner this time.
                MESSAGE;
                $this->telegramService->sendText(
                    $draw->getChat()->getChatId(),
                    sprintf($message, $winningNumber),
                    $draw->getTelegramThreadId(),
                    parseMode: 'HTML',
                );
                $nextDraw = DrawFactory::create($draw->getChat(), new \DateTime('+1 day'), $draw->getTelegramThreadId());
                $nextDraw->setPreviousDraw($draw);
                $nextDraw->setPreviousJackpot($draw->getJackpot());
            } else {
                $jackpot = $draw->getJackpot();
                $amountPerWinner = $jackpot->divide($winners->count());
                foreach ($winners as $winner) {
                    $message = <<<MESSAGE
                    Ehre Millions
                    The number is <strong>%s</strong>
                    
                    @%s
                    WINS %s Ehre
                    MESSAGE;
                    $this->telegramService->sendText(
                        $draw->getChat()->getChatId(),
                        sprintf(
                            $message,
                            $winner->getUser()->getName() ?? $winner->getUser()->getFirstName(),
                            $winningNumber,
                            NumberFormat::money($amountPerWinner),
                        ),
                        $draw->getTelegramThreadId(),
                        parseMode: 'HTML',
                    );
                    $this->honorService->addHonor($draw->getChat(), $winner->getUser(), $amountPerWinner);
                }
                $nextDraw = DrawFactory::create($draw->getChat(), new \DateTime('+1 day'), $draw->getTelegramThreadId());
                $nextDraw->setChat($draw->getChat());
                $nextDraw->setPreviousDraw(null);
                $nextDraw->setPreviousJackpot(Honor::currency(100_000));
            }
            $this->manager->persist($nextDraw);
            $this->manager->flush();
        }
        return Command::SUCCESS;
    }

}
