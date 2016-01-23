<?php

namespace Cosmologist\Gears;

use Cosmologist\Gears\Object\Exception\PropertyNotFoundException;

/**
 * Collection of commonly used methods for working with objects
 */
class Object
{
    /**
     * Return the value of the object property
     *
     * If the property is not available, try to find and use a getter (property(), getProperty(), get_property())
     *
     * @param object $object Object
     * @param string $propertyName Property name
     *
     * @return mixed
     *
     * @throws PropertyNotFoundException When the the property is not exist
     */
    public static function get($object, $propertyName)
    {
        if (array_key_exists($propertyName, get_object_vars($object))) {
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

        throw new PropertyNotFoundException(sprintf('Property #%s does not exists', $propertyName));
    }
}