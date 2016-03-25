<?php

namespace Cosmologist\Gears\Obj;

use Cosmologist\Gears\Obj;

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
     * @param object $object Object
     * @param string $propertyName Property name
     * @param bool $addSourceObjectToResult Also add source object to result
     *
     * @return array
     *
     */
    public static function get($object, $propertyName, $addSourceObjectToResult = false)
    {
        $result = $addSourceObjectToResult ? [$object] : [];

        $items = Obj::get($object, $propertyName);
        if (!is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $item) {
            if ($item !== null) {
                $result = array_merge($result, self::get($item, $propertyName, true));
            }
        }

        return $result;
    }
}