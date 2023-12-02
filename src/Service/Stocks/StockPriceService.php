<?php declare(strict_types=1);

namespace App\Service\Stocks;

use App\Entity\Stocks\Stock\Stock;
use App\Entity\Stocks\Stock\StockFactory;
use App\Entity\Stocks\Stock\StockPrice;
use App\Entity\Stocks\Stock\StockPriceFactory;
use App\Exception\StockSymbolUpdateException;
use App\Repository\Stocks\PortfolioRepository;
use App\Repository\Stocks\StockRepository;
use App\Utils\RateLimitUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Finnhub\Api\DefaultApi;
use Finnhub\ApiException;
use Finnhub\Configuration;
use Finnhub\Model\SymbolLookupInfo;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class StockPriceService
{

    public const STOCK_UPDATE_INTERVAL_HOURS = 12;

    private DefaultApi $client;

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $manager,
        private StockRepository $stockRepository,
        protected PortfolioRepository $portfolioRepository,
        string $finnhubApiKey,
    ) {
        $config = Configuration::getDefaultConfiguration()->setApiKey('token', $finnhubApiKey);
        if ($_ENV['APP_ENV'] === 'dev') {
            $client = new Client([
                'verify' => false,
            ]);
        } else {
            $client = new Client();
        }
        $this->client = new DefaultApi($client, $config);
    }

    public function getPriceBySymbol(string $symbol): ?StockPrice
    {
        $stock = $this->stockRepository->getBySymbol($symbol);
        if ($stock === null) {
            $stock = $this->fetchExactSymbol($symbol);
        }
        return $this->getPriceByStock($stock);
    }

    public function getPriceByStock(Stock $stock): StockPrice
    {
        $latestPrice = $stock->getLatestStockPrice();
        if ($latestPrice === null) {
            return $this->fetchCurrentPrice($stock);
        }
        if ($this->shouldFetchNewPrice($latestPrice)) {
            return $this->fetchCurrentPrice($stock);
        }
        return $latestPrice;
    }

    private function shouldFetchNewPrice(StockPrice $stockPrice): bool
    {
        $now = new \DateTime();
        // Always update if the price is older than a day
        if (RateLimitUtils::getDaysFrom($stockPrice->getCreatedAt()) > 0) {
            return true;
        }
        return RateLimitUtils::getHoursFrom($stockPrice->getCreatedAt()) >= self::STOCK_UPDATE_INTERVAL_HOURS;
    }

    /**
     * @param string $symbol
     * @return Collection<Stock>
     */
    public function fetchSymbol(string $symbol): Collection
    {
        try {
            $lookup = @$this->client->symbolSearch($symbol);
            if ($lookup->getCount() === 0 || $lookup->getResult() === null) {
                $this->logger->notice(sprintf('No results found for symbol %s', $symbol));
                throw new StockSymbolUpdateException($symbol, 'Symbol not found');
            }
            $matches = array_filter(
                $lookup->getResult(),
                fn (SymbolLookupInfo $info) => strstr(strtolower($info->getSymbol()), strtolower($symbol))
            );
            if (count($matches) === 0) {
                $this->logger->notice(sprintf('No match found for symbol %s', $symbol));
                throw new StockSymbolUpdateException($symbol, 'Failed to find symbol match');
            }
            $stocks = array_map(function (SymbolLookupInfo $info) {
                $stock = StockFactory::createFromLookupInfo($info);
                $existing = $this->stockRepository->getBySymbol($stock->getSymbol());
                if ($existing !== null) {
                    return $existing;
                }
                $this->manager->persist($stock);
                return $stock;
            }, $matches);
            $this->manager->flush();
            return new ArrayCollection($stocks);
        } catch (ApiException $exception) {
            $this->logger->error($exception->getMessage());
            throw new StockSymbolUpdateException($symbol, previous: $exception);
        }
    }

    private function fetchExactSymbol(string $symbol): Stock
    {
        try {
            $lookup = @$this->client->symbolSearch($symbol);
            if ($lookup->getCount() === 0 || $lookup->getResult() === null) {
                $this->logger->notice(sprintf('No results found for symbol %s', $symbol));
                throw new StockSymbolUpdateException($symbol, 'Symbol not found');
            }
            $exactMatch = array_filter($lookup->getResult(), fn (SymbolLookupInfo $info) => $info->getSymbol() === $symbol);
            if (count($exactMatch) === 0) {
                $this->logger->notice(sprintf('No exact match found for symbol %s', $symbol));
                throw new StockSymbolUpdateException($symbol, 'Failed to find exact symbol match');
            }
            $stock = StockFactory::createFromLookupInfo(array_values($exactMatch)[0]);
            $this->manager->persist($stock);
            $this->manager->flush();
            return $stock;
        } catch (ApiException $exception) {
            $this->logger->error($exception->getMessage());
            throw new StockSymbolUpdateException($symbol, previous: $exception);
        }
    }

    private function fetchCurrentPrice(Stock $stock): StockPrice
    {
        try {
            $quote = @$this->client->quote($stock->getSymbol());
            $price = StockPriceFactory::createFromQuote($stock, $quote);
            $stock->addStockPrice($price);
            $this->manager->flush();
            return $price;
        } catch (ApiException $exception) {
            $this->logger->error($exception->getMessage());
            throw new StockSymbolUpdateException($stock->getSymbol(), previous: $exception);
        }
    }

}
