<?php

namespace App\Command;

use App\Entity\WasteDisposal\WasteDisposalDate;
use App\Repository\ChatSubscriptionRepository;
use App\Repository\WasteDisposalDateRepository;
use App\Service\TelegramBaseService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dates = $this->dateRepository->getAllByDate(new \DateTime());
        if (count($dates) === 0) {
            $this->logger->info('No waste disposal dates found for today');
            return Command::SUCCESS;
        }
        foreach ($dates as $date) {
            $this->sendToSubscriber(sprintf('Morgen ist %s in Zone %s', $date->getDescription(), $date->getZone()));
        }
        return Command::SUCCESS;
    }

    private function sendToSubscriber(string $text): void
    {
        $subscriptions = $this->subscriptionRepository->getByType(WasteDisposalDate::SUBSCRIPTION_TYPE);
        foreach ($subscriptions as $subscription) {
            $this->telegramService->sendText($subscription->getChatId(), $text);
        }
    }

}