<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Message\Message;
use App\Exception\StockSymbolUpdateException;
use TelegramBot\Api\Types\Update;

class ShowStockPriceChatCommand extends AbstractStockChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/!stock (?<symbol>\w+)/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $symbol = $matches['symbol'];
        try {
            $price = $this->getStockPrice($symbol);
            $portfolio = $this->getPortfolioByMessage($message);
            $balance = $portfolio->getTransactionsBySymbol($symbol, $price);
            $this->telegramService->replyTo($message, sprintf(
                "<strong>%s</strong>\n%s\nSymbol: <strong>%s</strong>\n\n<code>$%.2f</code> (<code>%d Ehre</code>)\n\nYou have %d\ntotal: <code>$%.2f</code> <code>%d Ehre</code>",
                $price->getStock()->getName(),
                $price->getStock()->getType(),
                $price->getStock()->getDisplaySymbol(),
                $price->getPrice(),
                $price->getHonorPrice(),
                $balance->getTotalAmount(),
                $balance->getCurrentTotal(),
                $balance->getCurrentHonorTotal(),
            ), parseMode: 'HTML');
        } catch (StockSymbolUpdateException $exception) {
            $this->telegramService->replyTo($message, sprintf(
                'Failed to get stock price for %s [%s]',
                $symbol,
                $exception->getMessage()
            ));
        }
    }

}