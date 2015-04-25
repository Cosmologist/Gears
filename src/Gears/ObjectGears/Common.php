<?php

namespace Cosmologist\Gears\ObjectGears;

/**
 * Collection of commonly used methods for working with objects
 */
class Common
{
    /**
     * Return the value of the object property
     *
     * If the property is not available, try to find and use a getter (property(), getProperty(), get_property())
     *
     * @param $object
     * @param $propertyName
     * @return mixed
     */
    public static function get($object, $propertyName)
    {
        if (isset($object, $propertyName)) {
            return $object->$propertyName;
        }

        $possiblePropertyNames = [
            $propertyName,
            'get' . $propertyName,
            'get_' . $propertyName
        ];

        foreach ($possiblePropertyNames as $possiblePropertyName) {
            if (is_callable([$object, $possiblePropertyName])) {
                return $object->$possiblePropertyName();
            }
        }

        throw new PropertyNotFoundException;
    }
}