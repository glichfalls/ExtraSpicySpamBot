<?php

namespace App\Service\Stocks;

use App\Entity\Stocks\Stock\Stock;
use App\Entity\Stocks\Stock\StockFactory;
use App\Entity\Stocks\Stock\StockPrice;
use App\Entity\Stocks\Stock\StockPriceFactory;
use App\Exception\StockSymbolUpdateException;
use App\Repository\Stocks\StockRepository;
use App\Utils\RateLimitUtils;
use Doctrine\ORM\EntityManagerInterface;
use Finnhub\Api\DefaultApi;
use Finnhub\ApiException;
use Finnhub\Configuration;
use Finnhub\Model\SymbolLookupInfo;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class StockService
{

    public const STOCK_UPDATE_INTERVAL_MINUTES = 5;

    private DefaultApi $client;

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $manager,
        private StockRepository $stockRepository,
        string $finnhubApiKey,
    )
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('token', $finnhubApiKey);
        $this->client = new DefaultApi(new Client(), $config);
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
        // Always update if the price is older than 5 minutes and it's trading hours
        if ($now->format('H') >= 8 || $now->format('H') <= 23) {
            return RateLimitUtils::getMinutesFrom($stockPrice->getCreatedAt()) >= self::STOCK_UPDATE_INTERVAL_MINUTES;
        }
        // Don't update on weekends
        if ($now->format('N') >= 6) {
            return false;
        }
        // Don't update outside of trading hours
        if ($stockPrice->getCreatedAt()->format('H') < 23 || $stockPrice->getCreatedAt()->format('H') > 15) {
            return true;
        }
        return false;
    }

    private function fetchExactSymbol(string $symbol): Stock
    {
        try {
            $lookup = @$this->client->symbolSearch($symbol);
            if ($lookup->getCount() === 0 || $lookup->getResult() === null) {
                $this->logger->notice(sprintf('No results found for symbol %s', $symbol));
                throw new StockSymbolUpdateException($symbol, 'Symbol not found');
            }
            $exactMatch = array_filter($lookup->getResult(), fn(SymbolLookupInfo $info) => $info->getSymbol() === $symbol);
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
            throw new StockSymbolUpdateException($symbol, $exception);
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