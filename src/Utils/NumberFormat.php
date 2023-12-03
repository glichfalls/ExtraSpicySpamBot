<?php declare(strict_types=1);

namespace App\Utils;

use App\Entity\Honor\Honor;
use Money\Money;

class NumberFormat
{

    public const NUMBER_NAMES = [
        // this is the highest supported number
        // decimal can only hold 65 digits and 2 are used for the decimal point
        63 => 'Vigintillion',
        60 => 'Novemdecillion',
        57 => 'Octodecillion',
        54 => 'Septendecillion',
        51 => 'Sexdecillion',
        48 => 'Quindecillion',
        45 => 'Quattuordecillion',
        42 => 'Tredecillion',
        39 => 'Duodecillion',
        36 => 'Undecillion',
        33 => 'Decillion',
        30 => 'Nonillion',
        27 => 'Octillion',
        24 => 'Septillion',
        21 => 'Sextillion',
        18 => 'Quintillion',
        15 => 'Quadrillion',
        12 => 'T',
        9 => 'B',
        6 => 'M',
        3 => 'K',
        0 => '',
    ];

    public static function money(Money $money): string
    {
        if ($money->greaterThanOrEqual(Honor::currency(100_000))) {
            return self::humanizeMoney($money);
        }
        return number_format((float) $money->getAmount(), thousands_separator: '\'');
    }

    public static function format(float|int|string $number): string
    {
        if (is_string($number)) {
            // TODO: refactor this
            $number = (float) $number;
        }
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
        foreach (self::NUMBER_NAMES as $exponent => $abbrev) {
            if (abs($number) >= pow(10, $exponent)) {
                $display = $number / pow(10, $exponent);
                $formatted = is_int($display) ? number_format($display) : number_format($display, 1);
                $result = str_replace('.0', '', $formatted);
                return strlen($abbrev) === 1 ? $result . $abbrev : $result . ' ' . $abbrev;
            }
        }
        return (string) $number;
    }

    public static function humanizeMoney(Money $money): string
    {
        foreach (self::NUMBER_NAMES as $exponent => $abbrev) {
            $n = bcpow('10', (string) $exponent);
            if ($money->absolute()->greaterThanOrEqual(Honor::currency($n))) {
                $result = bcdiv($money->getAmount(), $n, 2);
                if (str_ends_with($result, '.00')) {
                    $result = str_replace('.00', '', $result);
                }
                if (preg_match('/\.\d0$/', $result)) {
                    $result = substr($result, 0, -1);
                }
                return strlen($abbrev) === 1 ? $result . $abbrev : $result . ' ' . $abbrev;
            }
        }
        return $money->getAmount();
    }

    public static function dehumanize(string $number): ?string
    {
        if (!self::isHumanizedNumber($number)) {
            return null;
        }
        $number = strtolower(trim($number));
        foreach (self::NUMBER_NAMES as $multiplier => $suffix) {
            $suffix = strtolower(trim($suffix));
            if (str_ends_with($number, $suffix)) {
                $value = substr($number, 0, -strlen($suffix));
                return $value . str_repeat('0', $multiplier);
            }
            if (preg_match('/.*\^(?<exponent>\d+)$/', $number, $matches) === 1) {
                $exponent = $matches['exponent'];
                $value = substr($number, 0, -strlen(sprintf('^%s', $exponent)));
                return bcpow($value, $exponent);
            }
        }
        return $number;
    }

    public static function isHumanizedNumber(string $number): bool
    {
        $all = array_values(self::NUMBER_NAMES);
        array_pop($all);
        $group = implode('|', $all);
        $regex = sprintf('/^\d+(\.\d+)?(%s)$/i', $group);
        return preg_match($regex, $number) === 1 || preg_match('/.*\^\d+$/', $number) === 1;
    }

    /**
     * This method will return the integer value of a number string.
     * If the number is abbreviated (e.g. 1.2K), it will be unabbreviated (e.g. 1200).
     * If the number is not abbreviated, it will be cast to an integer.
     */
    public static function getStringValue(string $number, ?string $abbr = null): string
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
        return $number;
    }

    public static function getHonorValue(string $numbers, ?string $abbr = null): Money
    {
        $number = trim($numbers);
        if ($abbr !== null) {
            $numberWithAbbr = sprintf('%s%s', $number, $abbr);
            if (self::isHumanizedNumber($numberWithAbbr)) {
                return Honor::currency(self::dehumanize($numberWithAbbr));
            }
        }
        if (self::isHumanizedNumber($number)) {
            return Honor::currency(self::dehumanize($number));
        }
        return Honor::currency($number);
    }

}