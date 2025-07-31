<?php

namespace Cosmologist\Gears\Symfony\PropertyAccessor;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * @todo comment and readme
 */
class NullTolerancePropertyAccessor extends PropertyAccessor
{
    /**
     * Get the values of the object or of the array by the property path null-safety
     */
    public function getValue(object|array $objectOrArray, string|PropertyPathInterface $propertyPath): mixed
    {
        return parent::isReadable($objectOrArray, $propertyPath) ? parent::getValue($objectOrArray, $propertyPath) : null;
    }
}
