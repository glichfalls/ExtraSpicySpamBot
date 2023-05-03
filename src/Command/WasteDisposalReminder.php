<?php

namespace App\Command;

use App\Entity\WasteDisposal\WasteDisposalDate;
use App\Repository\ChatSubscriptionRepository;
use App\Repository\WasteDisposalDateRepository;
use App\Service\TelegramBaseService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:waste-disposal:reminder')]
class WasteDisposalReminder extends Command
{

    public function __construct(
        private LoggerInterface $logger,
        private WasteDisposalDateRepository $dateRepository,
        private ChatSubscriptionRepository $subscriptionRepository,
        private TelegramBaseService $telegramService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('debug', 'd', null, 'Debug mode');
        $this->addArgument('zipCode', InputArgument::OPTIONAL, 'Zip code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $zipCode = $input->getArgument('zipCode');
        $dates = $zipCode !== null
            ? $this->dateRepository->getAllByDateAndZipCode(new \DateTime(), $zipCode)
            : $this->dateRepository->getAllByDate(new \DateTime());
        if (count($dates) === 0) {
            $this->logger->info('No waste disposal dates found for today');
            if ($input->getOption('debug')) {
                $this->sendToSubscriber($zipCode, 'Morgen ist keine Sammlung');
            }
            return Command::SUCCESS;
        }
        foreach ($dates as $date) {
            $this->sendToSubscriber($zipCode, sprintf('Morgen ist %s in Zone %s', $date->getDescription(), $date->getZone()));
        }
        return Command::SUCCESS;
    }

    private function sendToSubscriber(?string $zipCode, string $text): void
    {
        $subscriptions = $this->subscriptionRepository->getByTypeAndParameterOrNull(WasteDisposalDate::SUBSCRIPTION_TYPE, $zipCode);
        foreach ($subscriptions as $subscription) {
            $this->logger->info(sprintf('Sending message to %s', $subscription->getChat()->getName()));
            $this->telegramService->sendText($subscription->getChat()->getChatId(), $text);
        }
    }

}