<?php

namespace App\Exception;

class NotEnoughHonorException extends \InvalidArgumentException
{

    public function __construct(private int $balance, private int $required)
    {
        parent::__construct();
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function getRequired(): int
    {
        return $this->required;
    }

}