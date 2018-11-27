<?php

namespace Cosmologist\Gears;

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
        $result = (float) $result ?? null;

        return $result;
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
}