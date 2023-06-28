<?php

namespace App\Service\Telegram\Stocks;

use App\Entity\Honor\HonorFactory;
use App\Entity\Message\Message;
use App\Entity\Stocks\Portfolio\Portfolio;
use App\Entity\Stocks\Portfolio\PortfolioFactory;
use App\Entity\Stocks\Stock\StockPrice;
use App\Entity\Stocks\Transaction\StockTransaction;
use App\Entity\Stocks\Transaction\StockTransactionFactory;
use App\Exception\AmountZeroOrNegativeException;
use App\Exception\NotEnoughHonorException;
use App\Exception\NotEnoughStocksException;
use App\Repository\HonorRepository;
use App\Repository\Stocks\PortfolioRepository;
use App\Repository\Stocks\StockTransactionRepository;
use App\Service\HonorService;
use App\Service\Stocks\StockService;
use App\Service\Telegram\AbstractTelegramChatCommand;
use App\Service\Telegram\TelegramService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractStockChatCommand extends AbstractTelegramChatCommand
{

    public function __construct(
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TelegramService $telegramService,
        protected HonorService $honorService,
        protected HonorRepository $honorRepository,
        protected StockService $stockService,
        protected StockTransactionRepository $stockTransactionRepository,
        protected PortfolioRepository $portfolioRepository,
    )
    {
        parent::__construct($manager, $translator, $logger, $telegramService);
    }

    private function createStockTransaction(Portfolio $portfolio, string $symbol, int $amount): StockTransaction
    {
        $price = $this->stockService->getPriceBySymbol($symbol);
        $transaction = StockTransactionFactory::create($price, $amount);
        $portfolio->addTransaction($transaction);
        return $transaction;
    }

    protected function getStockPrice(string $symbol): StockPrice
    {
        return $this->stockService->getPriceBySymbol($symbol);
    }

    protected function getPortfolioByMessage(Message $message): Portfolio
    {
        $portfolio = $this->portfolioRepository->getByChatAndUser($message->getChat(), $message->getUser());
        if ($portfolio === null) {
            $portfolio = PortfolioFactory::create($message->getChat(), $message->getUser());
            $this->manager->persist($portfolio);
            $this->manager->flush();
        }
        return $portfolio;
    }

    protected function buyStock(Portfolio $portfolio, string $symbol, int $amount): StockTransaction
    {
        if ($amount <= 0) {
            throw new AmountZeroOrNegativeException('you cant buy 0 or less stocks');
        }
        $honor = $this->honorRepository->getHonorCount($portfolio->getUser(), $portfolio->getChat());
        if ($honor <= 0) {
            throw new AmountZeroOrNegativeException('you dont have enough honor');
        }
        $transaction = $this->createStockTransaction($portfolio, $symbol, $amount);
        if ($transaction->getHonorTotal() > $honor) {
            throw new NotEnoughHonorException($honor, $transaction->getHonorTotal());
        }
        $this->manager->persist(HonorFactory::createNegative(
            $portfolio->getChat(),
            $portfolio->getUser(),
            $transaction->getHonorTotal(),
        ));
        $this->manager->flush();
        return $transaction;
    }

    protected function sellStock(Portfolio $portfolio, string $symbol, int $amount): StockTransaction
    {
        if ($amount <= 0) {
            throw new AmountZeroOrNegativeException('you cant sell 0 or less stocks');
        }
        $transactions = $portfolio->getTransactionsBySymbol($symbol);
        if ($transactions->getTotalAmount() < $amount) {
            throw new NotEnoughStocksException($transactions->getTotalAmount(), $amount);
        }
        $transaction = $this->createStockTransaction($portfolio, $symbol, -$amount);
        $this->manager->persist(HonorFactory::createPositive(
            $portfolio->getChat(),
            $portfolio->getUser(),
            $transaction->getHonorTotal(),
        ));
        $this->manager->flush();
        return $transaction;
    }

}