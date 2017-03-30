<?php

namespace Cosmologist\Gears;

/**
 * Collection of commonly used math methods
 */
class Math
{
    /**
     * Returns the standard deviation
     *
     * This user-land implementation follows the implementation quite strictly;
     * it does not attempt to improve the code or algorithm in any way. It will
     * raise a warning if you have fewer than 2 values in your array, just like
     * the extension does (although as an E_USER_WARNING, not E_WARNING).
     *
     * @param array $a
     * @param bool  $sample [optional] Defaults to false
     *
     * @see http://php.net/manual/en/function.stats-standard-deviation.php
     * @see http://php.net/manual/ru/function.stats-standard-deviation.php#114473
     *
     * @return float|bool The standard deviation or false on error.
     */
    public static function standardDeviation(array $a, $sample = false)
    {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);

            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);

            return false;
        }
        $mean  = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d     = ((double) $val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
            --$n;
        }

        return sqrt($carry / $n);
    }
}