<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Message\Message;
use App\Exception\AmountZeroOrNegativeException;
use App\Exception\NotEnoughStocksException;
use App\Exception\StockSymbolUpdateException;
use TelegramBot\Api\Types\Update;

class SellStockChatCommand extends AbstractStockChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/!sell\s*(stocks|stock)?\s*(?<symbol>[.\w]+)\s*(?<amount>\d+|max)/', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $symbol = $matches['symbol'];
        $amount = $matches['amount'];
        try {
            $portfolio = $this->getPortfolioByMessage($message);
            if (strtolower($matches['amount']) === 'max') {
                $amount = $portfolio->getTransactionsBySymbol($symbol)->getTotalAmount();
            } else {
                $amount = (int) $amount;
            }
            $transaction = $this->sellStock($portfolio, $symbol, $amount);
            $this->telegramService->replyTo($message, sprintf(
                '%sx %s sold for %s Ehre',
                number_format(abs($transaction->getAmount()), thousands_separator: '\''),
                $transaction->getPrice()->getStock()->getDisplaySymbol(),
                number_format(abs($transaction->getHonorTotal()), thousands_separator: '\''),
            ));
        } catch (AmountZeroOrNegativeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        } catch (NotEnoughStocksException $exception) {
            $this->telegramService->replyTo($message, sprintf(
                'You dont have enough %s stocks to sell (you have %d stocks, you need %d stocks)',
                $symbol,
                $exception->getAvailable(),
                $exception->getRequired(),
            ));
        } catch (StockSymbolUpdateException $exception) {
            $this->telegramService->replyTo($message, sprintf(
                'Failed to get stock price for %s [%s]',
                $symbol,
                $exception->getMessage()
            ));
        }
    }

}