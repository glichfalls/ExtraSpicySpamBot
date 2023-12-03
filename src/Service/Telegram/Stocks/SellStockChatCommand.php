<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Chat\Chat;
use App\Entity\Message\Message;
use App\Entity\User\User;
use App\Exception\AmountZeroOrNegativeException;
use App\Exception\NotEnoughStocksException;
use App\Exception\StockSymbolUpdateException;
use App\Service\Stocks\StockService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramCallbackQueryListener;
use App\Service\Telegram\TelegramService;
use App\Utils\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TelegramBot\Api\Types\Update;

class SellStockChatCommand extends AbstractTelegramChatCommand implements TelegramCallbackQueryListener
{

    public const SELL_KEYWORD = 'stock:sell:max';

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        private readonly StockService $stockService,
    ) {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    public function matches(Update $update, Message $message, array &$matches): bool
    {
        return preg_match('/!sell\s*(stocks|stock)?\s*(?<symbol>[.\w]+)\s*(?<amount>\d+|max)/', $message->getMessage(), $matches) === 1;
    }

    public function handle(Update $update, Message $message, array $matches): void
    {
        $symbol = $matches['symbol'];
        try {
            $portfolio = $this->stockService->getPortfolioByChatAndUser($message->getChat(), $message->getUser());
            if (strtolower($matches['amount']) === 'max') {
                $amount = $portfolio->getTransactionsBySymbol($symbol)->getTotalAmount();
            } else {
                $amount = NumberFormat::getStringValue($matches['amount']);
            }
            $transaction = $this->stockService->sellStock($portfolio, $symbol, $amount);
            $this->telegramService->replyTo($message, sprintf(
                '%sx %s sold for %s Ehre',
                NumberFormat::format(bcmul($transaction->getAmount(), '-1')),
                $transaction->getPrice()->getStock()->getDisplaySymbol(),
                NumberFormat::money($transaction->getHonorTotal()->absolute()),
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