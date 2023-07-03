<?php

namespace App\Extension;

use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

class StockExtension extends AbstractExtension
{

    public function getFilters(): array
    {
        return [
            new TwigFilter('stock_price', [$this, 'stockPriceFilter']),
        ];
    }

    public function stockPriceFilter(?float $price): Markup|string
    {
        if ($price === null) {
            return 'N/A';
        }
        $html = sprintf('<code>$%.2f</code> (%d Ehre)', $price, round($price));
        return new Markup($html, 'UTF-8');
    }

}