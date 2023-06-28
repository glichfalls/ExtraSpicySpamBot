<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Message\Message;
use App\Entity\Stocks\Portfolio\Portfolio;
use App\Exception\StockSymbolUpdateException;
use TelegramBot\Api\Types\Update;

class ShowPortfolioChatCommand extends AbstractStockChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/^!portfolio/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        try {
            $portfolio = $this->getPortfolioByMessage($message);
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
        $total = 0;
        $totalHonor = 0;
        foreach ($portfolio->getBalance() as $transactions) {
            $currentPrice = $this->getStockPrice($transactions->getSymbol());
            $total += $transactions->getCurrentTotal($currentPrice);
            $totalHonor += $transactions->getCurrentHonorTotal($currentPrice);
            $data[] = sprintf(
                '%dx <strong>%s</strong>: %d Ehre (<code>$%.2f</code>)',
                $transactions->getTotalAmount(),
                $transactions->getSymbol(),
                $transactions->getCurrentHonorTotal($currentPrice),
                $transactions->getCurrentTotal($currentPrice),
            );
        }
        $data[] = sprintf('Total: <code>%.2f</code> (%d Ehre)', $total, $totalHonor);
        return implode(PHP_EOL, $data);
    }

}