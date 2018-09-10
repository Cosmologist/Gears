<?php

namespace Cosmologist\Gears;

use Cosmologist\Gears\Json\Exception\JsonParseException;
use stdClass;

/**
 * Collection of commonly used methods for working with JSON
 */
class Json
{

    /**
     * Takes a JSON encoded string and converts it into a PHP variable.
     *
     * json_decode wrapper.
     * If error occurred then throws JsonParseException with error message.
     *
     *
     * @param string $json           The json string being decoded.
     * @param bool   $assoc          When TRUE, returned objects will be converted into associative arrays.
     * @param int    $depth          User specified recursion depth.
     * @param bool   $bigIntAsString Allows casting big integers to string instead of floats which is the default.
     *
     * @return array|stdClass
     */
    public static function decode(string $json, bool $assoc = false, int $depth = 512, bool $bigIntAsString = false)
    {
        $options = $bigIntAsString === true ? JSON_BIGINT_AS_STRING : 0;

        $result = json_decode($json, $assoc, $depth, $options);

        if (null === $result) {
            throw new JsonParseException();
        }

        return $result;
    }

    /**
     * Takes a JSON encoded string and converts it into a PHP associative array.
     *
     * json_decode wrapper.
     * If error occurred then throws JsonParseException with error message.
     *
     * @param string $json           The json string being decoded.
     * @param int    $depth          User specified recursion depth.
     * @param bool   $bigIntAsString Allows casting big integers to string instead of floats which is the default.
     *
     * @return array
     */
    public static function decodeToArray(string $json, int $depth = 512, bool $bigIntAsString = false): array
    {
        $options = $bigIntAsString === true ? JSON_BIGINT_AS_STRING : 0;

        $result = json_decode($json, true, $depth, $options);

        if (null === $result) {
            throw new JsonParseException();
        }

        return $result;
    }
}