<?php

namespace App\Command;

use App\Entity\Chat\Chat;
use App\Repository\ChatRepository;
use App\Service\MemeService;
use App\Service\TelegramBaseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('telegram:memes:saturday-sailor')]
class SaturdaySailorCommand extends Command
{

    public function __construct(private MemeService $memeService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->memeService->saturdaySailor();
    }

}