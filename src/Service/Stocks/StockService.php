<?php declare(strict_types=1);

namespace App\Service\Stocks;

use App\Entity\Chat\Chat;
use App\Entity\Honor\HonorFactory;
use App\Entity\Stocks\Portfolio\Portfolio;
use App\Entity\Stocks\Portfolio\PortfolioFactory;
use App\Entity\Stocks\Transaction\StockTransaction;
use App\Entity\Stocks\Transaction\StockTransactionFactory;
use App\Entity\User\User;
use App\Exception\AmountZeroOrNegativeException;
use App\Exception\NotEnoughHonorException;
use App\Exception\NotEnoughStocksException;
use App\Repository\HonorRepository;
use App\Repository\Stocks\PortfolioRepository;
use App\Repository\Stocks\StockTransactionRepository;
use App\Service\Honor\HonorService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class StockService
{

    public function __construct(
        private StockPriceService $stockPriceService,
        private EntityManagerInterface $manager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        protected HonorService $honorService,
        protected HonorRepository $honorRepository,
        protected StockPriceService $stockService,
        protected StockTransactionRepository $stockTransactionRepository,
        protected PortfolioRepository $portfolioRepository,
    ) {

    }

    public function getPortfolioByChatAndUser(Chat $chat, User $user): Portfolio
    {
        $portfolio = $this->portfolioRepository->getByChatAndUser($chat, $user);
        if ($portfolio === null) {
            $portfolio = PortfolioFactory::create($chat, $user);
            $this->manager->persist($portfolio);
            $this->manager->flush();
        }
        return $portfolio;
    }

    public function getPortfolioBalance(Portfolio $portfolio): int|float
    {
        $total = 0;
        foreach ($portfolio->getBalance() as $transactions) {
            if ($transactions->getTotalAmount() === 0) {
                continue;
            }
            $currentPrice = $this->stockPriceService->getPriceBySymbol($transactions->getSymbol());
            $total += $transactions->getCurrentHonorTotal($currentPrice);
        }
        return $total;
    }

    private function createStockTransaction(Portfolio $portfolio, string $symbol, int $amount): StockTransaction
    {
        $price = $this->stockPriceService->getPriceBySymbol($symbol);
        if ($price->getHonorPrice() <= 0) {
            throw new AmountZeroOrNegativeException(sprintf('Stock price for %s is zero', $symbol));
        }
        $transaction = StockTransactionFactory::create($price, $amount);
        $portfolio->addTransaction($transaction);
        return $transaction;
    }

    public function buyStock(Portfolio $portfolio, string $symbol, int $amount): StockTransaction
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

    public function sellStock(Portfolio $portfolio, string $symbol, int $amount): StockTransaction
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
            $transaction->getHonorTotal() * -1,
        ));
        $this->manager->flush();
        return $transaction;
    }

}