<?php

namespace App\Utils;

class NumberFormat
{

    public const ABBREVIATION = [
        18 => 'Qi',
        15 => 'Q',
        12 => 'T',
        9 => 'B',
        6 => 'M',
        3 => 'K',
        0 => '',
    ];

    public static function format(float|int $number): string
    {
        if ($number >= 1_000) {
            return self::abbreviateNumber($number);
        }
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
                $decimals = (int) ($exponent >= 3 && $display < 100);
                $display = floor($display * pow(10, $decimals)) / pow(10, $decimals);
                return number_format($display, $decimals) . $abbrev;
            }
        }
        return (string) $number;
    }

    public static function unabbreviateNumber(string $number): ?int
    {
        if (!self::isAbbreviatedNumber($number)) {
            return null;
        }
        $number = strtoupper(trim($number));
        foreach (self::ABBREVIATION as $multiplier => $suffix) {
            if (str_ends_with($number, $suffix)) {
                $value = substr($number, 0, -strlen($suffix));
                return (int) $value . str_repeat('0', $multiplier);
            }
        }
        return (int) $number;
    }

    public static function isAbbreviatedNumber(string $number): bool
    {
        $suffixGroup = implode('|', array_values(self::ABBREVIATION));
        return preg_match('/^\d+(\.\d+)?(' . $suffixGroup . ')$/i', $number) === 1;
    }

    /**
     * This method will return the integer value of a number string.
     * If the number is abbreviated (e.g. 1.2K), it will be unabbreviated (e.g. 1200).
     * If the number is not abbreviated, it will be cast to an integer.
     */
    public static function getIntValue(string $number, ?string $abbr = null): int
    {
        $number = trim($number);
        if ($abbr !== null) {
            $numberWithAbbr = sprintf('%s%s', $number, $abbr);
            if (self::isAbbreviatedNumber($numberWithAbbr)) {
                return self::unabbreviateNumber($numberWithAbbr);
            }
        }
        if (self::isAbbreviatedNumber($number)) {
            return self::unabbreviateNumber($number);
        }
        return (int) $number;
    }

}