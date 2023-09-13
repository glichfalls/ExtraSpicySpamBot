<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Message\Message;
use App\Entity\Stocks\Stock\Stock;
use App\Entity\Stocks\Stock\StockPrice;
use App\Entity\Stocks\Transaction\SymbolTransactionCollection;
use App\Exception\StockSymbolUpdateException;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class ShowStockPriceChatCommand extends AbstractStockChatCommand
{

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/!stock (?<symbol>.+)/i', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $symbol = $matches['symbol'];
        try {
            try {
                $price = $this->getStockPrice($symbol);
            } catch (StockSymbolUpdateException) {
                $searchResult = $this->searchStock($symbol);
                $this->telegramService->sendText($message->getChat()->getChatId(), sprintf(
                    'Stock symbol %s not found. Did you mean one of:%s %s?',
                    $symbol,
                    PHP_EOL,
                    implode(
                        PHP_EOL,
                        $searchResult
                        ->map(fn (Stock $stock) => sprintf('<code>%s</code>: %s', $stock->getSymbol(), $stock->getName()))
                        ->slice(0, 10)
                    ),
                ), parseMode: 'HTML');
                return;
            }
            $this->telegramService->sendText(
                $message->getChat()->getChatId(),
                sprintf(
                    <<<TEXT
                    %s
                    %s
                    %s Ehre
                    TEXT,
                    $price->getStock()->getName(),
                    $price->getStock()->getSymbol(),
                    NumberFormat::format($price->getPrice()),
                ),
                threadId: $message->getTelegramThreadId(),
                replyMarkup: $this->getKeyboard($price->getStock()),
            );
        } catch (StockSymbolUpdateException $exception) {
            $this->telegramService->replyTo($message, sprintf(
                'Failed to get stock price for %s [%s]',
                $symbol,
                $exception->getMessage()
            ));
        }
    }

    private function sendHtmlReply(Message $message, SymbolTransactionCollection $balance, StockPrice $price): void
    {
        $this->telegramService->senderRenderedMessage(
            'stock',
            $message->getChat()->getChatId(),
            threadId: $message->getTelegramThreadId(),
            replyMarkup: $this->getKeyboard($price->getStock()),
            context: [
                'price' => $price,
                'balance' => $balance,
            ]
        );
    }

    private function getKeyboard(Stock $stock): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup([
            [
                [
                    'text' => 'buy',
                    'callback_data' => sprintf('%s:%s', BuyStockChatCommand::BUY_KEYWORD, $stock->getSymbol()),
                ],
                [
                    'text' => 'sell',
                    'callback_data' => sprintf('%s:%s', SellStockChatCommand::SELL_KEYWORD, $stock->getSymbol()),
                ],
            ],
        ]);
    }

}
