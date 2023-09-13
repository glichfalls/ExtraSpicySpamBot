<?php

namespace App\Utils;

class NumberFormat
{

    public const ABBREVIATION = [
        12 => 'T',
        9 => 'B',
        6 => 'M',
        3 => 'K',
        0 => '',
    ];

    public static function format(float|int $number): string
    {
        return number_format($number, thousands_separator: '\'');
    }

    public static function currency(float|int $amount, ?string $currency = null): string
    {
        $formatter = new \NumberFormatter('de_DE', \NumberFormatter::CURRENCY);
        if ($currency !== null) {
            $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, $currency);
        }
        return $formatter->formatCurrency($amount, $currency);
    }

    public static function abbreviateNumber(float|int $number): string
    {
        foreach (self::ABBREVIATION as $exponent => $abbrev) {
            if (abs($number) >= pow(10, $exponent)) {
                $display = $number / pow(10, $exponent);
                $decimals = ($exponent >= 3 && round($display) < 100) ? 1 : 0;
                $number = number_format($display, $decimals) . $abbrev;
                break;
            }
        }

        return $number;
    }

    public static function unabbreviateNumber(string $number): int
    {
        $number = str_replace('K', '000', $number);
        $number = str_replace('M', '000000', $number);
        $number = str_replace('B', '000000000', $number);
        $number = str_replace('T', '000000000000', $number);
        return (int) $number;
    }

    public static function isAbbreviatedNumber(string $number): bool
    {
        if (preg_match('/[KMBT]/', $number) === false) {
            return false;
        }
        $number = str_replace(['K', 'M', 'B', 'T'], '', $number);
        return is_numeric(trim($number));
    }

}