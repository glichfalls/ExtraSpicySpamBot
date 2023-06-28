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
            $this->telegramService->replyTo($message, sprintf(
                '%s (%s): $%d (%d Ehre)',
                $price->getStock()->getName(),
                $price->getStock()->getDisplaySymbol(),
                $price->getPrice(),
                $price->getHonorPrice(),
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