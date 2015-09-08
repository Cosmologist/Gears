<?php

namespace Cosmologist\Gears\Object;

use Cosmologist\Gears\Object\Exception\PropertyNotFoundException;

/**
 * Collection of methods for recursive access to object properties
 */
class PropertyRecursiveAccess
{
    /**
     * Get the values of a property recursively
     *
     * <code>
     * $grandfather = new Person();
     *
     * $dad = new Person();
     * $dad->setParent($grandfather);
     *
     * $i = new Person();
     * $i->setParent($dad);
     *
     * $parents = PropertyRecursiveAccess::get($i, 'parent');
     *
     * var_dump($parents); // Return the objects of dad and grandfather
     * </code>
     *
     *
     * @param object $object Object
     * @param string $propertyName Property name
     *
     * @return array
     *
     * @throws PropertyNotFoundException When the the property is not exist
     */
    public static function get($object, $propertyName)
    {
        $result = [];

        $items = Common::get($object, $propertyName);

        if (!is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $item) {
            if ($item !== null) {
                $result[] = $item;
                $result = array_merge($result, self::get($item, $propertyName));
            }
        }

        return $result;
    }


    /**
     * Get the final values of a property recursively
     *
     * The final value - the value that contains the null in the property
     *
     * <code>
     * $grandfather = new Person();
     *
     * $dad = new Person();
     * $dad->setParent($grandfather);
     *
     * $i = new Person();
     * $i->setParent($dad);
     *
     * $parents = PropertyRecursiveAccess::get($i, 'parent');
     *
     * var_dump($parents); // Return the objects of dad and grandfather
     * </code>
     *
     *
     * @param object $object Object
     * @param string $propertyName Property name
     *
     * @return array
     *
     * @throws PropertyNotFoundException When the the property is not exist
     */
    public static function getLast($object, $propertyName)
    {
        $result = [];

        $items = self::get($object, $propertyName);
        foreach ($items as $item) {
            if (Common::get($item, $propertyName) === null) {
                $result[] = $item;
            }
        }

        return $result;
    }
}