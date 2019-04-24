<?php

namespace Cosmologist\Gears;

use ReflectionClass;

/**
 * Collection of commonly used methods for working with classes
 */
class ClassType
{
    /**
     * Returns class short name
     *
     * @param string|object String containing the name of the class or an object.
     *
     * @return string The class short name.
     */
    public static function shortName($class): string
    {
        return (new ReflectionClass($class))->getShortName();
    }
}