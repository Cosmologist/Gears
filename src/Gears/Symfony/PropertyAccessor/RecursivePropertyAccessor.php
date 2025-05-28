<?php

namespace Cosmologist\Gears\Symfony\PropertyAccessor;

use Cosmologist\Gears\ArrayType;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class RecursivePropertyAccessor extends PropertyAccessor
{
    /**
     * Get the values of the property path of the object or of the array recursively
     *
     *  <code>
     *  use Cosmologist\Gears\Symfony\PropertyAccessor\RecursivePropertyAccessor;
     *
     *  $grandfather = new Person(name: 'grandfather');
     *  $dad = new Person(name: 'dad', parent: $grandfather);
     *  $i = new Person(name: 'i', parent: $dad);
     *
     *  (new RecursivePropertyAccessor())->getValue($i, 'parent'); // [Person(dad), Person(grandfather)]
     *  </code>
     */
    #[Override]
    public function getValue(object|array $objectOrArray, PropertyPathInterface|string $propertyPath): mixed
    {
        $result = [];

        // Cast parent::getValue() result to an array (not traversable value cast to [value])
        $values = ArrayType::toArray(parent::getValue($objectOrArray, $propertyPath));

        foreach ($values as $value) {
            $result[] = $value;

            if (is_array($value) || is_object($value)) {
                $result = array_merge($result, self::getValue($value, $propertyPath));
            }
        }

        return $result;
    }
}
