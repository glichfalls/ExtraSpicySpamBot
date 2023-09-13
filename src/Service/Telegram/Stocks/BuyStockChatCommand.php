<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Message\Message;
use App\Exception\AmountZeroOrNegativeException;
use App\Exception\NotEnoughHonorException;
use App\Exception\StockSymbolUpdateException;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Update;

class BuyStockChatCommand extends AbstractStockChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/!buy\s*(stocks|stock)?\s*(?<symbol>[.\w]+)\s*(?<amount>\d+|max)/', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $symbol = $matches['symbol'];
        $amount = $matches['amount'];
        if ($amount === 'max') {
            $price = $this->stockService->getPriceBySymbol($symbol);
            $honor = $this->honorRepository->getHonorCount($message->getUser(), $message->getChat());
            $amount = floor($honor / $price->getHonorPrice());
            if ($amount <= 0) {
                $this->telegramService->replyTo($message, sprintf(
                    'You dont have enough Ehre to buy %s',
                    $symbol,
                ));
                return;
            }
        } else {
            $amount = (int) $amount;
        }
        try {
            $portfolio = $this->getPortfolioByMessage($message);
            $transaction = $this->buyStock($portfolio, $symbol, $amount);
            $this->telegramService->replyTo($message, sprintf(
                "<strong>%s</strong>\n%s\n\n%sx %s: <code>%s</code> Ehre\nTotal buy Price: <code>%s</code> Ehre",
                $transaction->getPrice()->getStock()->getName(),
                $transaction->getPrice()->getStock()->getType(),
                NumberFormat::format($transaction->getAmount()),
                $transaction->getPrice()->getStock()->getDisplaySymbol(),
                $transaction->getPrice()->getHonorPrice(),
                NumberFormat::format($transaction->getHonorTotal()),
            ), parseMode: 'HTML');
        } catch (AmountZeroOrNegativeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        } catch (NotEnoughHonorException $exception) {
            $this->telegramService->replyTo($message, sprintf(
                'You dont have enough Ehre to buy %sx %s (you have %s Ehre, you need %s Ehre)',
                NumberFormat::format($amount),
                $symbol,
                NumberFormat::format($exception->getBalance()),
                NumberFormat::format($exception->getRequired()),
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