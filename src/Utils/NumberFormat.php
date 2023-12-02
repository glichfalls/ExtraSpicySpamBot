<?php

namespace App\Utils;

use App\Entity\Honor\Honor;
use Money\Money;

class NumberFormat
{

    public const ABBREVIATION = [
        18 => 'Qi', // quadrillion
        15 => 'Q',
        12 => 'T',
        9 => 'B',
        6 => 'M',
        3 => 'K',
        0 => '',
    ];

    public static function money(Money $money): string
    {
        return self::format($money->getAmount());
    }

    public static function format(float|int $number): string
    {
        if ($number >= 1_000) {
            return self::humanize($number);
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

    public static function humanize(float|int $number): string
    {
        foreach (self::ABBREVIATION as $exponent => $abbrev) {
            if (abs($number) >= pow(10, $exponent)) {
                $display = $number / pow(10, $exponent);
                $formatted = is_int($display) ? number_format($display) : number_format($display, 1);
                return str_replace('.0', '', $formatted) . $abbrev;
            }
        }
        return (string) $number;
    }

    public static function dehumanize(string $number): ?string
    {
        if (!self::isHumanizedNumber($number)) {
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

    public static function isHumanizedNumber(string $number): bool
    {
        $all = array_values(self::ABBREVIATION);
        array_pop($all);
        $group = implode('|', $all);
        $regex = sprintf('/^\d+(\.\d+)?(%s)$/i', $group);
        return preg_match($regex, $number) === 1;
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
            if (self::isHumanizedNumber($numberWithAbbr)) {
                return self::dehumanize($numberWithAbbr);
            }
        }
        if (self::isHumanizedNumber($number)) {
            return self::dehumanize($number);
        }
        return (int) $number;
    }

    public function toHonor(?string $amount, ?string $abbr = null): Money
    {
        $number = trim($amount);
        if ($abbr !== null) {
            $numberWithAbbr = sprintf('%s%s', $number, $amount);
            if (self::isHumanizedNumber($numberWithAbbr)) {
                return Honor::currency(self::dehumanize($numberWithAbbr));
            }
        }
        if (self::isHumanizedNumber($amount)) {
            return Honor::currency(self::dehumanize($amount));
        }
        return Honor::currency($amount);
    }

}