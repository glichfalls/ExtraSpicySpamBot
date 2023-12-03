<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Honor\Honor;
use App\Entity\Message\Message;
use App\Entity\Stocks\Portfolio\Portfolio;
use App\Exception\StockSymbolUpdateException;
use App\Service\Stocks\StockPriceService;
use App\Service\Stocks\StockService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

final class ShowPortfolioChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly StockService $stockService,
        private readonly StockPriceService $stockPriceService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!portfolio/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $portfolio = $this->stockService->getPortfolioByChatAndUser($message->getChat(), $message->getUser());
            $this->telegramService->replyTo($message, $this->getBalance($portfolio), parseMode: 'HTML');
        } catch (StockSymbolUpdateException $exception) {
            $this->telegramService->replyTo($message, sprintf(
                'Failed to get portfolio price [%s]',
                $exception->getMessage()
            ));
        }
    }

    private function getBalance(Portfolio $portfolio): string
    {
        $data = [];
        $totalHonor = Honor::currency(0);
        foreach ($portfolio->getBalance() as $transactions) {
            if (bccomp($transactions->getTotalAmount(), '0') === 0) {
                continue;
            }
            $currentPrice = $this->stockPriceService->getPriceBySymbol($transactions->getSymbol());
            $totalHonor = $totalHonor->add($transactions->getCurrentHonorTotal($currentPrice));
            $data[] = sprintf(
                '%sx <strong>%s</strong>: %s Ehre',
                NumberFormat::format($transactions->getTotalAmount()),
                $transactions->getSymbol(),
                NumberFormat::money($transactions->getCurrentHonorTotal($currentPrice)),
            );
        }
        $data[] = sprintf(
            '<strong>Total</strong>: %s Ehre',
            NumberFormat::money($totalHonor),
        );
        return implode(PHP_EOL, $data);
    }

}
