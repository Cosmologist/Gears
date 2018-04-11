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

## String functions
##### Replace first string occurrence in an another string
```php
StringType::replaceFirst('name name name', 'name', 'title'); // 'title name name'
```