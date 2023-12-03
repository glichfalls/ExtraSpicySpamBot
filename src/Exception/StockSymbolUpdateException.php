<?php declare(strict_types=1);

namespace App\Exception;

use Finnhub\ApiException;

class StockSymbolUpdateException extends \RuntimeException
{

    public function __construct(private readonly string $symbol, string $message = '', ?ApiException $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

}