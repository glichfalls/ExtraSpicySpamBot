<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\Stocks\Transaction\StockTransaction;
use App\Entity\User\User;
use App\Exception\AmountZeroOrNegativeException;
use App\Exception\NotEnoughHonorException;
use App\Exception\StockSymbolUpdateException;
use App\Service\Telegram\TelegramCallbackQueryListener;
use TelegramBot\Api\Types\Update;

class BuyStockChatCommand extends AbstractStockChatCommand implements TelegramCallbackQueryListener
{
    public const BUY_KEYWORD = 'stock:buy';

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/!buy\s*(stocks|stock)?\s*(?<symbol>[.\w]+)\s*(?<amount>\d+|max)/', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $symbol = $matches['symbol'];
        $amount = $matches['amount'];
        try {
            $transaction = $this->buy($message->getChat(), $message->getUser(), $symbol, $amount);
            $this->telegramService->replyTo($message, $this->getTransactionBill($transaction), parseMode: 'HTML');
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
            $this->logger->error($exception->getMessage());
            $this->telegramService->replyTo($message, sprintf(
                'Failed to get stock price for %s [%s]',
                $symbol,
                $exception->getMessage()
            ));
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    public function getCallbackKeyword(): string
    {
        return self::BUY_KEYWORD;
    }

    public function handleCallback(Update $update, Chat $chat, User $user): void
    {
        try {
            $callbackQuery = $update->getCallbackQuery();
            $parts = explode(':', $callbackQuery->getData());
            $transaction = $this->buy($chat, $user, end($parts), 'max');
            $this->telegramService->sendText(
                $chat->getChatId(),
                $this->getTransactionBill($transaction),
                threadId: $callbackQuery->getMessage()->getMessageThreadId(),
                parseMode: 'HTML',
            );
            $this->telegramService->answerCallbackQuery(
                $callbackQuery,
                'Stock bought',
                true,
            );
        } catch (AmountZeroOrNegativeException $exception) {
            $this->telegramService->answerCallbackQuery(
                $callbackQuery,
                $exception->getMessage(),
                true,
            );
        } catch (StockSymbolUpdateException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function buy(Chat $chat, User $user, string $symbol, string $amount): StockTransaction
    {
        $portfolio = $this->getPortfolioByUserAndChat($chat, $user);
        if ($amount === 'max') {
            $price = $this->stockService->getPriceBySymbol($symbol);
            $honor = $this->honorRepository->getHonorCount($user, $chat);
            $amount = floor($honor / $price->getHonorPrice());
            if ($amount <= 0) {
                throw new NotEnoughHonorException($honor, $price->getHonorPrice());
            }
        } else {
            $amount = (int) $amount;
        }
        return $this->buyStock($portfolio, $symbol, $amount);
    }

    private function getTransactionBill(StockTransaction $transaction): string
    {
        return sprintf(
            "<strong>%s</strong>\n%s\n\n%dx %s: <code>%s</code> Ehre\nTotal buy Price: <code>%d</code> Ehre",
            $transaction->getPrice()->getStock()->getName(),
            $transaction->getPrice()->getStock()->getType(),
            $transaction->getAmount(),
            $transaction->getPrice()->getStock()->getDisplaySymbol(),
            $transaction->getPrice()->getHonorPrice(),
            number_format($transaction->getHonorTotal(), thousands_separator: '\''),
        );
    }

}