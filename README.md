# php-gears
Collection of useful functions

## Installation
```
composer require cosmologist/gears
```

## Array functions

##### Check if array is associative
```php
ArrayType::checkAssoc([1, 2, 3]); // false
ArrayType::checkAssoc(['foo' => 'bar']); // true
```

##### Convert list of items to ranges
```php
ArrayType::ranges([1, 3, 7, 9]); // [[1, 3], [3, 7], [7, 9]]
```

##### Unset array item by value
```php
ArrayType::unsetValue(['a', 'b', 'c'], 'b'); // ['a', 'c']
```

## Math functions

##### Round to nearest multiple
```php
MathType::roundStep(50, 5); // 50
MathType::roundStep(52, 5); // 50
MathType::roundStep(53, 5); // 55
```

##### Round down to nearest multiple
```php
MathType::floorStep(50, 5); // 50
MathType::floorStep(52, 5); // 50
MathType::floorStep(53, 5); // 50
```

##### Round up to nearest multiple
```php
MathType::ceilStep(50, 5); // 50
MathType::ceilStep(52, 5); // 55
MathType::ceilStep(53, 5); // 55
```

## Object functions
##### Reads value of internal object property (protected and private)
Read [ocramius](https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/)
```php
ObjectType::readInternalProperty($object, $property);
```
##### Writes value to internal object property (protected and private)
Read [ocramius](https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/)
```php
ObjectType::writeInternalProperty($object, $property, $value);
```

## String functions
##### Determine if a given string contains a given substring
```php
StringType::contains('Foo', 'Bar'); // false
StringType::contains('FooBar', 'Bar'); // true
```
##### Replace first string occurrence in an another string
```php
StringType::replaceFirst('name name name', 'name', 'title'); // 'title name name'
```
##### Wrap string
```php
StringType::wrap('target', '/'); // '/target/'
```