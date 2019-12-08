<?php

namespace Cosmologist\Gears;

use Locale;
use NumberFormatter;

class NumberType
{
    /**
     * Extract number from a string
     *
     * @param string}int}float $value Value
     *
     * @return float|null
     */
    public static function parse($value): ?float
    {
        $result = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND | FILTER_FLAG_ALLOW_SCIENTIFIC);

        return $result === false ? null : (float) $result;
    }

    /**
     * Checks if the value is odd
     *
     * @param int $value
     *
     * @return bool
     */
    public static function odd(int $value): bool
    {
        return boolval($value & 1);
    }

    /**
     * Checks if the value is even
     *
     * @param int $value
     *
     * @return bool
     */
    public static function even(int $value): bool
    {
        return !self::odd($value);
    }

    /**
     * Round value to nearest multiple
     *
     * @param int|float $value Value
     * @param int       $step  Round step
     *
     * @return float|int
     */
    public static function roundStep($value, int $step = 1)
    {
        return round($value / $step) * $step;
    }

    /**
     * Round value down to nearest multiple
     *
     * @param int|float $value Value
     * @param int       $step  Round step
     *
     * @return float|int
     */
    public static function floorStep($value, int $step = 1)
    {
        return floor($value / $step) * $step;
    }

    /**
     * Round value up to nearest multiple
     *
     * @param int|float $value Value
     * @param int       $step  Round step
     *
     * @return float|int
     */
    public static function ceilStep($value, int $step = 1)
    {
        return ceil($value / $step) * $step;
    }

    /**
     * Spell out a number.
     *
     * If strange behavior occurs - check if the latest version of ICU is installed (libicu, not php-intl extension).
     *
     * @link https://php.net/manual/en/numberformatter.format.php
     * @todo Required Intl decorator implementation to convert Intl errors to Exceptions
     *
     * @param int|float   $value  The value to format. Can be integer or float,
     *                            other values will be converted to a numeric value.
     * @param string|null $locale Locale in which the number would be formatted (locale name, e.g. en_CA)
     *
     * @return string|false
     */
    public static function spellout($value, $locale = null)
    {
        return NumberFormatter::create($locale ?? Locale::getDefault(), NumberFormatter::SPELLOUT)->format($value);
    }

    /**
     * Division with zero tolerance
     *
     * @param float|int $left          Left operand
     * @param float|int $right         Right operand
     * @param null      $fallbackValue Value to return when the right operand is zero
     *
     * @return float|int|null
     */
    public static function divideSafely($left, $right, $fallbackValue = null)
    {
        return $right === 0 || $right === null ? $fallbackValue : ($left / $right);
    }
}