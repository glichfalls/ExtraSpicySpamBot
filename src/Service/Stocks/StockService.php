<?php

namespace App\Service\Stocks;

use App\Entity\Honor\Stocks\Stock\Stock;
use App\Entity\Honor\Stocks\Stock\StockPrice;
use App\Repository\Stocks\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Finnhub\Api\DefaultApi;
use Finnhub\ApiException;
use Finnhub\Configuration;
use GuzzleHttp\ClientInterface;

class StockService
{

    private DefaultApi $client;

    public function __construct(
        private EntityManagerInterface $manager,
        private StockRepository $stockRepository,
        ClientInterface $client,
        string $finnhubApiKey,
    )
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('token', $finnhubApiKey);
        $this->client = new DefaultApi($client, $config);
    }

    private function getCurrentPrice(Stock $stock): ?int
    {
        try {
            $this->client->quote($stock->getSymbol());
            return null;
        } catch (ApiException $exception) {
            return null;
        }
    }

}