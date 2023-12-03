<?php declare(strict_types=1);

namespace App\Service\Telegram\Stocks;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
use App\Entity\Message\Message;
use App\Entity\Stocks\Transaction\StockTransaction;
use App\Entity\User\User;
use App\Exception\AmountZeroOrNegativeException;
use App\Exception\NotEnoughHonorException;
use App\Exception\NumberTooHighException;
use App\Exception\StockSymbolUpdateException;
use App\Service\Honor\HonorService;
use App\Service\Stocks\StockPriceService;
use App\Service\Stocks\StockService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class BuyStockChatCommand extends AbstractTelegramChatCommand implements TelegramCallbackQueryListener
{
    public const BUY_KEYWORD = 'stock:buy';

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly StockService $stockService,
        private readonly StockPriceService $stockPriceService,
        private readonly HonorService $honorService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

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
                'You dont have enough Ehre to buy %sx %s (you have %s Ehre, you need %s Ehre)',
                NumberFormat::format($amount),
                $symbol,
                NumberFormat::money($exception->getBalance()),
                NumberFormat::money($exception->getRequired()),
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
        $callbackQuery = $update->getCallbackQuery();
        try {
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
        $portfolio = $this->stockService->getPortfolioByChatAndUser($chat, $user);
        if ($amount === 'max') {
            $price = $this->stockPriceService->getPriceBySymbol($symbol);
            if ($price->getHonorPrice()->lessThanOrEqual(Honor::currency(0))) {
                throw new AmountZeroOrNegativeException(sprintf('Stock price for %s is zero', $symbol));
            }
            $honor = $this->honorService->getCurrentHonorAmount($chat, $user);
            $amount = $honor->divide($price->getHonorPrice()->getAmount());
            if ($amount->greaterThan(Honor::currency(PHP_INT_MAX))) {
                $amount = Honor::currency(PHP_INT_MAX);
            }
            if ($amount->lessThanOrEqual(Honor::currency(0))) {
                throw new NotEnoughHonorException($honor, $price->getHonorPrice());
            }
            $amount = $amount->getAmount();
        }
        return $this->stockService->buyStock($portfolio, $symbol, $amount);
    }

    private function getTransactionBill(StockTransaction $transaction): string
    {
        return sprintf(
            '%s x %s bought for %s Ehre',
            NumberFormat::format($transaction->getAmount()),
            $transaction->getPrice()->getStock()->getDisplaySymbol(),
            NumberFormat::money($transaction->getHonorTotal()),
        );
    }

}
