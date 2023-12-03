<?php declare(strict_types=1);

namespace App\Dto;

use App\Entity\User\User;
use Money\Money;

readonly class NetWorth
{

    public function __construct(
        public User $user,
        public Money $cash,
        public Money $balance,
        public Money $portfolio,
    ) {
    }

    public function getTotal(): Money
    {
        return $this->cash->add($this->balance)->add($this->portfolio);
    }

}