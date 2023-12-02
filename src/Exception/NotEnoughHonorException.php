<?php declare(strict_types=1);

namespace App\Exception;

use Money\Money;

class NotEnoughHonorException extends \InvalidArgumentException
{

    public function __construct(private readonly Money $balance, private readonly Money $required)
    {
        parent::__construct();
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function getRequired(): Money
    {
        return $this->required;
    }

}