<?php

namespace Cosmologist\Gears;

/**
 * Collection of commonly used methods for working with classes
 */
class ClassType
{
    /**
     * Gets short namey
     *
     * If the property is not available, try to find and use a getter (property(), getProperty(), get_property())
     *
     * @param string|object String containing the name of the class or an object.
     *
     * @return string The class short name.
     */
    public static function getShortName($class)
    {
        return (new \ReflectionClass($class))->getShortName();
    }
}