<?php

namespace Cosmologist\Gears\Symfony\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException as SymfonyValidationFailedException;

class ValidationFailedException extends SymfonyValidationFailedException
{
    /**
     * Simple and convenient way instance of ValidationFailedException with single ConstraintViolation
     *
     * <code>
     * Cosmologist\Gears\Symfony\Validator\ValidationFailedException::violate($foo, "Foo with invalid bar");
     * Cosmologist\Gears\Symfony\Validator\ValidationFailedException::violate($foo, "Foo with invalid {{ bar }}", compact('bar'));
     * Cosmologist\Gears\Symfony\Validator\ValidationFailedException::violate($foo, "Foo with invalid bar", propertyPath: 'bar');
     * </code>
     */
    public static function violate(mixed $value, string $message, array $parameters = [], ?string $propertyPath = null): self
    {
        return new self(
            $value,
            new ConstraintViolationList(
                [new ConstraintViolation(
                    $message,
                    $message,
                    $parameters,
                    $value,
                    $propertyPath,
                    null
                )]
            )
        );
    }
}
