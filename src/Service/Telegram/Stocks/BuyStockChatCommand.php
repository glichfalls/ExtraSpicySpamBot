<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Message\Message;
use App\Exception\AmountZeroOrNegativeException;
use App\Exception\NotEnoughHonorException;
use App\Exception\StockSymbolUpdateException;
use TelegramBot\Api\Types\Update;

class BuyStockChatCommand extends AbstractStockChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/!buy stock?s (?<symbol>\w+) (?<amount>\d+)/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $symbol = $matches['symbol'];
        $amount = (int) $matches['amount'];
        try {
            $portfolio = $this->getPortfolioByMessage($message);
            $transaction = $this->buyStock($portfolio, $symbol, $amount);
            $this->telegramService->replyTo($message, sprintf(
                "<strong>%s</strong>\n%s\n\n%dx %s: <code>$%.2f</code> (%d Ehre)\nTotal buy Price: %d Ehre",
                $transaction->getPrice()->getStock()->getName(),
                $transaction->getPrice()->getStock()->getType(),
                $transaction->getAmount(),
                $transaction->getPrice()->getStock()->getDisplaySymbol(),
                $transaction->getPrice()->getPrice(),
                $transaction->getPrice()->getHonorPrice(),
                $transaction->getHonorTotal(),
            ), parseMode: 'HTML');
        } catch (AmountZeroOrNegativeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        } catch (NotEnoughHonorException $exception) {
            $this->telegramService->replyTo($message, sprintf(
                'You dont have enough Ehre to buy %dx %s (you have %d Ehre, you need %d Ehre)',
                $amount,
                $symbol,
                $exception->getBalance(),
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