<?php

namespace App\Command;

use App\Service\MemeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('telegram:memes:weekday-sailor')]
class WeekdaySailorCommand extends Command
{

    public function __construct(private MemeService $memeService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->memeService->weekdaySailor(date('l'));
    }

}