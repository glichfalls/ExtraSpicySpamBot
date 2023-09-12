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
        $totalHonor = 0;
        foreach ($portfolio->getBalance() as $transactions) {
            if ($transactions->getTotalAmount() === 0) {
                continue;
            }
            $currentPrice = $this->getStockPrice($transactions->getSymbol());
            $totalHonor += $transactions->getCurrentHonorTotal($currentPrice);
            $data[] = sprintf(
                '%dx <strong>%s</strong>: <code>%s</code> Ehre',
                $transactions->getTotalAmount(),
                $transactions->getSymbol(),
                number_format($transactions->getCurrentTotal($currentPrice), thousands_separator: '\''),
            );
        }
        $data[] = sprintf(
            'Total: <code>%s</code> Ehre',
            number_format($totalHonor, thousands_separator: '\''),
        );
        return implode(PHP_EOL, $data);
    }

}