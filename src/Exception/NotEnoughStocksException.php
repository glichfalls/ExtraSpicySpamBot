<?php declare(strict_types=1);

namespace App\Exception;

class NotEnoughStocksException extends \InvalidArgumentException
{

    public function __construct(private readonly string $available, private readonly string $required)
    {
        parent::__construct();
    }

    public function getAvailable(): string
    {
        return $this->available;
    }

    public function getRequired(): string
    {
        return $this->required;
    }

}