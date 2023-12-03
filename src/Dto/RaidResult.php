<?php declare(strict_types=1);

namespace App\Dto;

use App\Entity\Honor\Raid\Raid;

readonly class RaidResult
{
    public function __construct(
        public Raid $raid,
        public bool $success,
    ) {

    }
}