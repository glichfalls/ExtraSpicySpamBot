<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Message\Message;
use App\Entity\Stocks\Stock\Stock;
use App\Exception\StockSymbolUpdateException;
use TelegramBot\Api\Types\Update;

class ShowStockPriceChatCommand extends AbstractStockChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/!stock (?<symbol>[.\w]+)/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $symbol = $matches['symbol'];
        try {
            try {
                $price = $this->getStockPrice($symbol);
            } catch (StockSymbolUpdateException) {
                $searchResult = $this->searchStock($symbol);
                $this->telegramService->replyTo($message, sprintf(
                    'Stock symbol %s not found. Did you mean one of %s?',
                    $symbol,
                    implode(',', $searchResult
                        ->map(fn(Stock $stock) => $stock->getSymbol())
                        ->getValues()
                    ),
                ));
            }
            $portfolio = $this->getPortfolioByMessage($message);
            $balance = $portfolio->getTransactionsBySymbol($symbol, $price);
            $this->telegramService->renderReplyTo($message, 'stock', [
                'price' => $price,
                'balance' => $balance,
            ]);
        } catch (StockSymbolUpdateException $exception) {
            $this->telegramService->replyTo($message, sprintf(
                'Failed to get stock price for %s [%s]',
                $symbol,
                $exception->getMessage()
            ));
        }
    }

}