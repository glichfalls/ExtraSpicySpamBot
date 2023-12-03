<?php declare(strict_types=1);

namespace App\Service\Stocks;

use App\Entity\Chat\Chat;
use App\Entity\Honor\Honor;
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
use Money\Money;
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

    public function getPortfolioBalance(Portfolio $portfolio): Money
    {
        $total = Honor::currency(0);
        foreach ($portfolio->getBalance() as $transactions) {
            if ($transactions->getTotalAmount() === '0') {
                continue;
            }
            $currentPrice = $this->stockPriceService->getPriceBySymbol($transactions->getSymbol());
            $total = $total->add($transactions->getCurrentHonorTotal($currentPrice));
        }
        return $total;
    }

    private function createStockTransaction(Portfolio $portfolio, string $symbol, string $amount): StockTransaction
    {
        $price = $this->stockPriceService->getPriceBySymbol($symbol);
        if ($price->getHonorPrice()->lessThanOrEqual(Honor::currency(0))) {
            throw new AmountZeroOrNegativeException(sprintf('Stock price for %s is zero', $symbol));
        }
        $transaction = StockTransactionFactory::create($price, $amount);
        $portfolio->addTransaction($transaction);
        return $transaction;
    }

    public function buyStock(Portfolio $portfolio, string $symbol, string $amount): StockTransaction
    {
        if (bccomp($amount, '0') <= 0) {
            throw new AmountZeroOrNegativeException('you cant buy 0 or less stocks');
        }
        $honor = $this->honorService->getCurrentHonorAmount($portfolio->getChat(), $portfolio->getUser());
        if ($honor->lessThanOrEqual(Honor::currency(0))) {
            throw new AmountZeroOrNegativeException('you dont have enough honor');
        }
        $transaction = $this->createStockTransaction($portfolio, $symbol, $amount);
        if ($transaction->getHonorTotal() > $honor) {
            throw new NotEnoughHonorException($honor, $transaction->getHonorTotal());
        }
        $this->honorService->removeHonor($portfolio->getChat(), $portfolio->getUser(), $transaction->getHonorTotal());
        $this->manager->flush();
        return $transaction;
    }

    public function sellStock(Portfolio $portfolio, string $symbol, string $amount): StockTransaction
    {
        if (bccomp($amount, '0') <= 0) {
            throw new AmountZeroOrNegativeException('you cant sell 0 or less stocks');
        }
        $transactions = $portfolio->getTransactionsBySymbol($symbol);
        if (bccomp($transactions->getTotalAmount(), $amount) < 0) {
            throw new NotEnoughStocksException($transactions->getTotalAmount(), $amount);
        }
        $transaction = $this->createStockTransaction($portfolio, $symbol, bcmul($amount, '-1'));
        $this->honorService->addHonor($portfolio->getChat(), $portfolio->getUser(), $transaction->getHonorTotal());
        $this->manager->flush();
        return $transaction;
    }

}