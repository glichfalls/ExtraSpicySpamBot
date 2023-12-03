<?php declare(strict_types=1);

namespace App\Tests\Number;

use App\Entity\Honor\Honor;
use App\Utils\NumberFormat;
use PHPUnit\Framework\TestCase;

class NumberFormatTest extends TestCase
{
    public function testMaxInt(): void
    {
        $nf = NumberFormat::class;
        // assuming 64 bit PHP
        $this->assertEquals('9.2 Quintillion', $nf::humanize(PHP_INT_MAX));
        $this->assertEquals('-9.2 Quintillion', $nf::humanize(-PHP_INT_MAX));
    }
    public function testMoney(): void
    {
        $nf = NumberFormat::class;
        $this->assertEquals('1', $nf::money(Honor::currency(1)));
        $this->assertEquals('99\'999', $nf::money(Honor::currency(99_999)));
        $this->assertEquals('100K', $nf::money(Honor::currency(100_000)));
        $this->assertEquals('1M', $nf::money(Honor::currency(1_000_000)));
        $this->assertEquals('1.5M', $nf::money(Honor::currency(1_500_000)));
        $this->assertEquals('1.45M', $nf::money(Honor::currency(1_450_000)));
        $this->assertEquals('1.49M', $nf::money(Honor::currency(1_499_999)));
        $this->assertEquals('1M', $nf::money(Honor::currency(1_000_001)));
        $this->assertEquals('1M', $nf::money(Honor::currency(1_009_999)));
        $this->assertEquals('1.01M', $nf::money(Honor::currency(1_010_000)));
        $this->assertEquals('1.01M', $nf::money(Honor::currency(1_019_999)));
        $this->assertEquals('1.1M', $nf::money(Honor::currency(1_100_000)));
    }
}