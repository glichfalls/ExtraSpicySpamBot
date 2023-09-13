<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Exception\AmountZeroOrNegativeException;
use App\Exception\NotEnoughStocksException;
use App\Exception\StockSymbolUpdateException;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Utils\NumberFormat;
use TelegramBot\Api\Types\Update;

class SellStockChatCommand extends AbstractStockChatCommand implements TelegramCallbackQueryListener
{

    public const SELL_KEYWORD = 'stock:sell:max';

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
                NumberFormat::format(abs($transaction->getAmount())),
                $transaction->getPrice()->getStock()->getDisplaySymbol(),
                NumberFormat::format(abs($transaction->getHonorTotal())),
            ));
        } catch (AmountZeroOrNegativeException $exception) {
            $this->telegramService->replyTo($message, $exception->getMessage());
        } catch (NotEnoughStocksException $exception) {
            $this->telegramService->replyTo($message, sprintf(
                'You dont have enough %s stocks to sell (you have %s stocks, you need %s stocks)',
                $symbol,
                NumberFormat::format($exception->getAvailable()),
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

    public function getCallbackKeyword(): string
    {
        return self::SELL_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        $this->telegramService->answerCallbackQuery($update->getCallbackQuery(), 'soon™', true);
    }

}