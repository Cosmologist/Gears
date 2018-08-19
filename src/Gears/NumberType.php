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
}