<?php

namespace App\Exception;

class NotEnoughStocksException extends \InvalidArgumentException
{

    public function __construct(private int $available, private int $required)
    {
        parent::__construct();
    }

    public function getAvailable(): int
    {
        return $this->available;
    }

    public function getRequired(): int
    {
        return $this->required;
    }

}