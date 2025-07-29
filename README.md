# PHP-Gears - handy helper library for PHP and Symfony

- [Installation](#installation)
- Common
  - [Array functions](#array-functions)
  - [Callable functions](#callable-functions)
  - [Class functions](#class-functions)
  - [File functions](#file-functions)
  - [Number functions](#number-functions)
  - [Object functions](#object-functions)
  - [String functions](#string-functions)
- [Doctrine Utils](#doctrine-utils)
- Symfony
  - [ExpressionLanguage utils](#symfony-expressionlanguage-utils)
  - [Form utils](#symfony-forms-utils)
  - [Framework utils](#symfony-framework-utils)
  - [Messenger utils](#symfony-messenger-utils)
  - [PropertyAccess utils](#symfony-propertyaccess-utils)
  - [Security utils](#symfony-security-utils)
  - [Validator utils](#symfony-validator-utils)

## Installation
```
composer require cosmologist/gears
```

## Array functions

### Get an item from the array by key
```php
ArrayType::get(['fruit' => 'apple', 'color' => 'red'], 'fruit'); // 'apple'
ArrayType::get(['fruit' => 'apple', 'color' => 'red'], 'weight'); // null
ArrayType::get(['fruit' => 'apple', 'color' => 'red'], 'weight', 15); // 15
ArrayType::get(['apple', 'red'], -1); // 'red'
```

### Adds a value to an array with a specific key only if key not presents in an array
It's more intuitive variant to `<code>$array += [$key => $value];`
```php
$array = ['fruit' => 'apple'];
ArrayType::touch($array, 'color', 'red']); // ['fruit' => 'apple', 'color' => 'red'];
ArrayType::touch($array, 'fruit', 'banana']); // ['fruit' => 'apple'];
```

### Push element onto the end of array and returns the modified array
```php
$a = [1,2];
ArrayType::push($a, 3); // [1,2,3]
```

### Prepend element to the beginning of an array and returns the modified array
```php
$a = [2,3];
ArrayType::unshift($a, 1); // [1,2,3]
```

### Calculate the average of values in an array (array_avg)
```php
ArrayType::average([1, 2, 3]); // 3
```

### Check if array is associative
```php
ArrayType::checkAssoc([1, 2, 3]); // false
ArrayType::checkAssoc(['foo' => 'bar']); // true
```

### Check if a value exists in an array
```php
ArrayType::contains(array $list, mixed $item);
```

### Inserts an array after the key
```php
ArrayType::insertAfter(['a' => 1, 'c' => 3], 'a', ['b' => 2]); // ['a' => 1, 'b' => 2, 'c' => 3]
// If the key doesn't exist
ArrayType::insertAfter(['a' => 1, 'b' => 2], 'c', ['foo' => 'bar']); // ['a' => 1, 'b' => 2, 'foo' => 'bar']
```

### Inserts an array before the key
```php
ArrayType::insertBefore(['a' => 1, 'c' => 3], 'c', ['b' => 2]); // ['a' => 1, 'b' => 2, 'c' => 3]
// If the key doesn't exist
ArrayType::insertBefore(['a' => 1, 'b' => 2], 'c', ['foo' => 'bar']); // ['foo' => 'bar', 'a' => 1, 'b' => 2]
```

### Convert list of items to ranges
```php
ArrayType::ranges([1, 3, 7, 9]); // [[1, 3], [3, 7], [7, 9]]
```

### Unset array item by value
```php
ArrayType::unsetValue(['a', 'b', 'c'], 'b'); // ['a', 'c']
```

### Calculate the standard deviation of values in an array
```php
ArrayType::deviation([1, 2, 1]); // float(0.4714045207910317)
```

### Cast to an array
Behavior for different types:
  - array - returns as is
  - iterable - converts to a native array (`iterator_to_array()`)
  - another - creates an array with argument ([value])
```php
ArrayType::toArray($value);
```

### Get the array encoded in json
If encoded value is false, true or null then returns empty array.  
JSON_THROW_ON_ERROR always enabled.
```php
ArrayType::fromJson($json): array;
```

## Callable functions

### Determine if a callable a closure
```php
CallableType::isClosure(fn($foo) => $foo); // bool(true)
CallableType::isClosure('foo'); // bool(false)
CallableType::isClosure([$foo, 'bar']); // bool(false)
CallableType::isClosure('Foo\Bar::baz'); // bool(false)
```

### Determine if a callable a function
```php
CallableType::isFunction(fn($foo) => $foo); // bool(false)
CallableType::isFunction('foo'); // bool(true)
CallableType::isFunction([$foo, 'bar']); // bool(false)
CallableType::isFunction('Foo\Bar::baz'); // bool(false)
```

### Determine if a callable a method
```php
CallableType::isMethod(fn($foo) => $foo); // bool(false)
CallableType::isMethod('foo'); // bool(false)
CallableType::isMethod([$foo, 'bar']); // bool(true)
CallableType::isMethod('Foo\Bar::baz'); // bool(true)
```

### Determine if a callable a static method
```php
CallableType::isStaticMethod(fn($foo) => $foo); // bool(false)
CallableType::isStaticMethod('foo'); // bool(false)
CallableType::isStaticMethod([$foo, 'bar']); // bool(false)
CallableType::isStaticMethod('Foo\Bar::baz'); // bool(true)
```

### Get suitable reflection implementation for the callable
```php
CallableType::reflection(fn($foo) => $foo); // object(ReflectionFunction)
CallableType::reflection('foo'); // object(ReflectionFunction)
CallableType::reflection([$foo, 'bar']); // object(ReflectionMethod)
CallableType::reflection('Foo\Bar::baz'); // object(ReflectionMethod)
```

## Class functions

### Get the class or an object class short name
```php
ClassType::short('Foo\Bar'); // "Bar"
ClassType::short(Foo\Bar::class); // "Bar"
ClassType::short(new Foo\Bar()); // "Bar"
```

### Get the class and the parent classes
```php
namespace Foo;

class Bar {};
class Baz extends Foo {};
...
ClassType::selfAndParents('Foo\Bar'); // ["Foo\Bar"]
ClassType::selfAndParents(Foo\Bar::class); // ["Foo\Bar"]
ClassType::selfAndParents(new Foo\Bar()); // ["Foo\Bar"]
ClassType::selfAndParents('Foo\Baz'); // ["Foo\Baz", "Foo\Bar"]
ClassType::selfAndParents(Foo\Baz::class); // ["Foo\Baz", "Foo\Bar"]
ClassType::selfAndParents(new Foo\Baz()); // ["Foo\Baz", "Foo\Bar"]
```

### Get the class and the parent classes
```php
namespace Foo;

class Bar {};
class Baz extends Foo {};
...
ClassType::selfAndParents('Foo\Bar'); // ["Foo\Bar"]
ClassType::selfAndParents(Foo\Bar::class); // ["Foo\Bar"]
ClassType::selfAndParents(new Foo\Bar()); // ["Foo\Bar"]
ClassType::selfAndParents('Foo\Baz'); // ["Foo\Baz", "Foo\Bar"]
ClassType::selfAndParents(Foo\Baz::class); // ["Foo\Baz", "Foo\Bar"]
ClassType::selfAndParents(new Foo\Baz()); // ["Foo\Baz", "Foo\Bar"]
```

### Get the corresponding basic enum case dynamically from variable
Basic enumerations does not implement from() or tryFrom() methods, but it is possible to return the corresponding enum case using the constant() function.
```php
ClassType::enumCase(FooEnum::class, 'bar');
```

## File functions

### Get the extension of a file name
```php
FileType::extension('/foo/bar.baz'); // 'baz'
FileType::extension('/foo/bar'); // ''
```

### Write a string to a file and create the file directory recursively if it does not exist
```php
FileType::put('/foo/bar.txt', 'baz');
```

### Get the path to the file with $name inside the system temporary directory
```php
FileType::temporary('foo.txt'); // '/tmp/foo.txt'
```

### Determine if the path an absolute path
```php
FileType::isAbsolutePath('C:/foo'); true
FileType::isAbsolutePath('C:\\bar'); true
FileType::isAbsolutePath('foo/bar'); false
FileType::isAbsolutePath('/foo/bar'); true
FileType::isAbsolutePath('\\foo\\bar'); true
```

### Join the paths into one and fix the directory separators
```php
FileType::joinPaths('a/', '/b/', '\\c', 'd'); // Return a/b/c/d
```

### Fix the directory separators (remove duplicates and replace with the current system directory separator)
```php
FileType::fixPath('/foo//bar\baz'); '/foo/bar/baz'
```

### Guess the file extensions of the file
```php
FileType::guessExtensions('/foo/bar.txt'); // ['txt']
FileType::guessExtensions('/foo/bar.jpg'); // ['jpeg', 'jpg', 'jpe', 'jfif']
```

### Guess the file extension of the file
```php
FileType::guessExtension('/foo/bar.txt'); // 'txt'
FileType::guessExtension('/foo/bar.jpg'); // 'jpeg'
```

### Guess the mime-type of the file
```php
FileType::guessMime('/foo/bar.txt'); // 'text/plain'
FileType::guessMime('/foo/bar.jpg'); // 'image/jpeg'
```

## Number functions

### Parse a float or integer value from the argument
Remove all characters except digits, +-.,eE from the argument and returns result as the float value or NULL if the parser fails.
```php
NumberType::parse(" 123 "); // int(123)
NumberType::parse(" 123.45 "); // float(123.45)
NumberType::parse(" 123.00 "); // int(123)
```

### Parse a float value from the argument
Remove all characters except digits, +-.,eE from the argument and returns result as the float value or NULL if the parser fails.
```php
NumberType::parseFloat(" 123 "); // float(123)
NumberType::parseFloat(" 123.45 "); // float(123.45)
```

### Parse a integer value from the argument
Remove all characters except digits, plus and minus sign and returns result as the integer value or NULL if the parser fails.
```php
NumberType::parseInteger(" 123 "); // int(123)
NumberType::parseFloat(" 123.45 "); // int(12345)
```

### Returns fractions of the float value
```php
NumberType::fractions(123.45); // float(0.45)
NumberType::parseFloat(123); // float(0)
```

### Checks if the value is odd
```php
NumberType::odd(2); // false
NumberType::odd(3); // true
```

### Checks if the value is even
```php
NumberType::even(2); // true
NumberType::even(3); // false
```

### Round to nearest multiple
```php
NumberType::roundStep(50, 5); // 50
NumberType::roundStep(52, 5); // 50
NumberType::roundStep(53, 5); // 55
```

### Round down to nearest multiple
```php
NumberType::floorStep(50, 5); // 50
NumberType::floorStep(52, 5); // 50
NumberType::floorStep(53, 5); // 50
```

### Round up to nearest multiple
```php
NumberType::ceilStep(50, 5); // 50
NumberType::ceilStep(52, 5); // 55
NumberType::ceilStep(53, 5); // 55
```

### Spell out
```php
// Current locale used
NumberType::spellout(123.45); // one hundred twenty-three point four five

// Specific locale used
NumberType::spellout(123.45, 'ru'); // сто двадцать три целых сорок пять сотых
```

### Division with zero tolerance
```php
NumberType::divideSafely(1, 0); // null
NumberType::divideSafely(1, null); // null
NumberType::divideSafely(1, 0, 'zero'); // 'zero'
```

### Percent calculation
The first argument is a value for calculating the percentage.
The second argument is a base value corresponding to 100%.
```php
NumberType::percentage(10, 100); // 10 
NumberType::percentage(100, 100); // 100  
NumberType::percentage(200, 100); // 200  
```

### Unsign a number
A negative value will be converted to zero, positive or zero value will be returned unchanged.
```php
NumberType::unsign(-1); // 0
NumberType::unsign(-0.99); // 0
NumberType::unsign(0); // 0
NumberType::unsign(0.99); // 0.99
NumberType::unsign(1); // 1
```

### Converts a number to string with sign.
```php
NumberType::toStringWithSign(-1); // "-1"
NumberType::toStringWithSign(1); // "+1"
NumberType::toStringWithSign(0); // "0"
```


## Object functions

### Read the value at the end of the property path of the object graph
```php
ObjectType::get($person, 'address.street');
```
Uses Symfony PropertyAccessor

### Read the value of internal object property (protected and private)
Read [ocramius](https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/)
```php
ObjectType::getInternal($object, $property);
```

### Get the values of the property path of the object recursively
Read [ocramius](https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/)
```php
$grandfather = new Person(name: 'grandfather');
$dad = new Person(name: 'dad', parent: $grandfather);
$i = new Person(name: 'i', parent: $dad);

ObjectType::getRecursive($i, 'parent'); // [Person(dad), Person(grandfather)]
```

### Set the value at the end of the property path of the object graph
```php
ObjectType::set($person, 'address.street', 'Abbey Road');
```
Uses Symfony PropertyAccessor

### Write the value to internal object property (protected and private)
Read [ocramius](https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/)
```php
ObjectType::setInternal($object, $property, $value);
```

### Call the internal object method (protected and private) and returns result
Read [ocramius](https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/)
```php
ObjectType::callInternal($object, $method, $arg1, $arg2, $arg3, ...);
```

### Get a string representation of the object or enum 
- Result of __toString method if presents
- String value of case for the BackedEnum
- Name of case for the UnitEnum
- or generated string like "FQCN@spl_object_id"

PHP default behavior: if the method is not defined, an error (`Object of class X could not be converted to string`) is triggered.
```php
namespace Foo;

class Bar {
}
class BarMagicMethod {
    public function __toString(): string {
        return 'Bar';
    }
}
enum BazUnitEnum {
    case APPLE;
} 
enum BazStringBackedEnum: string {
    case APPLE = 'apple';
}
enum BazIntBackedEnum: int {
    case APPLE = 1;
}

ObjectType::toString(new Foo); // 'Foo/Bar@1069'
ObjectType::toString(new FooMagicMethod); // 'Foo'
ObjectType::toString(BazUnitEnum::APPLE); // 'APPLE'
ObjectType::toString(BazStringBackedEnum::APPLE); // '1'
```

### Cast an object or a FQCN to FQCN
Returns the result of `__toString` or null if the method is not defined.  
PHP default behavior: if the method is not defined, an error (`Object of class X could not be converted to string`) is triggered.
```php
ObjectType::toClassName($objectOrClass): string;
```

## String functions

### Determine if a given string contains a given substring
```php
StringType::contains('Foo', 'Bar'); // false
StringType::contains('FooBar', 'Bar'); // true
```

### Simple symmetric decryption of a string with a key (using libsodium)
```php
StringType::decrypt(StringType::encrypt('The sensitive string', 'qwerty123456'), 'qwerty123456'); // 'The sensitive string'
```

### Simple symmetric encryption of a string with a key (using libsodium)
```php
StringType::encrypt('The sensitive string', 'qwerty123456');
```

### Convenient way to perform a regular expression match
Default behaviour like preg_match_all(..., ..., PREG_SET_ORDER)
```php
StringType::regexp('a1b2', '\S(\d)'); // [0 => [0 => 'a1', 1 => '1'], 1 => [0 => 'b2', 1 => '2']]
```
Exclude full matches from regular expression matches
```php
StringType::regexp('a1b2', '\S(\d)', true); // [0 => [0 => '1'], 1 => [0 => '2']]
```
Get only first set from regular expression matches (exclude full matches)
```php
StringType::regexp('a1b2', '(\S)(\d)', true, true); // [0 => 'a', 1 => '1']
```
Get only first match of each set from regular expression matches (exclude full matches)
```php
StringType::regexp('a1b2', '(\S)(\d)', true, false, true); // [0 => 'a', 1 => 'b']
```
Get only first match of the first set from regular expression matches as single scalar value
```php
StringType::regexp('a1b2', '(\S)(\d)', true, true, true); // 'a'
```

### Replace first string occurrence in a string
```php
StringType::replaceFirst('name name name', 'name', 'title'); // 'title name name'
```

### Wrap string
```php
StringType::wrap('target', '/'); // '/target/'
```

### Guess the MIME-type of the string data
```php
StringType::guessMime('foo bar'); // "text/plain"
StringType::guessMime(file_get_content("foo.jpg")); // "image/jpeg"
```

### Guess the file extension from the string data.
```php
StringType::guessExtension('foo bar'); // "txt"
StringType::guessExtension(file_get_content("foo.jpg")); // "jpeg"
```

### Check if a string is a binary string
```php
StringType::isBinary('Foo bar baz'); // false
```

### Convert string to CamelCase
```php
StringType::toCamelCase('string like this'); // 'StringLikeThis'
StringType::toCamelCase('string_like_this'); // 'StringLikeThis'
```

### Convert string to snake_case
```php
StringType::toSnakeCase('StringLikeThis'); // 'string_like_this'
StringType::toSnakeCase('string Like this'); // 'string_like_this'
```

### ltrim()/rtrim()/trim() replacements supports UTF-8 chars in the charlist
Use these only if you are supplying the charlist optional arg and it contains UTF-8 characters. Otherwise trim will work normally on a UTF-8 string.
```php
trim('«foo»', '»'); // "�foo"
StringType::trim('«foo»', '»'); // "«foo"
```

### Split text into sentences
```php
StringType::sentences('Fry me a Beaver. Fry me a Beaver! Fry me a Beaver? Fry me Beaver no. 4?! Fry me many Beavers... End);
```
returns
```php
[
  [0] => 'Fry me a Beaver.',
  [1] => 'Fry me a Beaver!',
  [2] => 'Fry me a Beaver?',
  [3] => 'Fry me Beaver no. 4?!',
  [4] => 'Fry me many Beavers...',
  [5] => 'End'
]
```

### Split text into words
```php
StringType::words('Fry me many Beavers... End'); // ['Fry', 'me', 'many', 'Beavers', 'End']
```

### Remove word from text
```php
StringType::unword('Remove word from text', 'word'); // 'Remove from text'
```

## Doctrine utils

### DoctrineUtils activation
Manually instance a DoctrineUtils
```php
public function __construct(private Doctrine\Persistence\ManagerRegistry $doctrine)
{
    $doctrineUtils = new Cosmologist\Gears\Doctrine\DoctrineUtils($doctrine);
}
```

Register DoctrineUtils as a service with Symfony DI
```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true

    Cosmologist\Gears\Doctrine\DoctrineUtils:
```

### Get metadata for a persistent object or a persistent object class
```php
$doctrineUtils->getClassMetadata(new App\Entity\User()); // object(ClassMetadata)
$doctrineUtils->getClassMetadata(App\Entity\User::class); // object(ClassMetadata)
$doctrineUtils->getClassMetadata(new App\Controller\FooController())); // null
$doctrineUtils->getClassMetadata(App\Controller\FooController::class); // null
```

### Get real class of a persistent object (resolve a proxy class)
```php
$doctrineUtils->getRealClass(Proxies\__CG__\App\Entity\User::class); // 'App\Entity\User'
$doctrineUtils->getRealClass(new Proxies\__CG__\App\Entity\User()); // 'App\Entity\User'
$doctrineUtils->getRealClass(App\Entity\User::class); // 'App\Entity\User'
$doctrineUtils->getRealClass(new App\Entity\User()); // 'App\Entity\User'
$doctrineUtils->getRealClass(new App\Controller\FooController()); // null
$doctrineUtils->getRealClass(App\Controller\FooController::class); // null
```

### Check if an object, or an object class persistent (managed by the Doctrine)
```php
$doctrineUtils->isManaged(new MyEntity()); // true
$doctrineUtils->isManaged(new stdClass()); // false
```

### Get an identifier field name of the Doctrine object
```php
$doctrineUtils->getSingleIdentifierField(new MyEntityWithSingleIdentifier(id: 1000)); // 'id'
$doctrineUtils->getSingleIdentifierField(new MyEntityWithMultipleIdentifiers()); // \Assert\InvalidArgumentException
$doctrineUtils->getSingleIdentifierField(new stdClass); // \Assert\InvalidArgumentException
```

### Get an identifier value of the Doctrine object
```php
$doctrineUtils->getSingleIdentifierValue(new MyEntityWithSingleIdentifier(id: 1000)); // 1000
$doctrineUtils->getSingleIdentifierValue(new MyEntityWithMultipleIdentifiers()); // \Assert\InvalidArgumentException
$doctrineUtils->getSingleIdentifierValue(new stdClass); // \Assert\InvalidArgumentException
```

### Merge multiple Doctrine\Common\Collections\Criteria into a new one
```php
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr;

DoctrineUtils::mergeCriteria(
    new Criteria(new Expr\Comparison('status', Expr\Comparison::EQ, 'new')),
    new Criteria(new Expr\Comparison('type', Expr\Comparison::NEQ, 'foo'))
);
```

### Add a join to a QueryBuilder with support of the nested join (e.g. "contact.user.type")
```php
$qb = $entityManager->getRepository(Company::class)->createQueryBuilder('company');
DoctrineUtils::join($qb, 'contact.user.type');
// equivalent to
$qb
  ->join('company.contact', 'contact')
  ->join('contact.user', 'user')
  ->join('user.type', 'type');
```

### Add a join to a QueryBuilder once and returns an alias of join
```php
$qb = $entityManager->getRepository(Company::class)->createQueryBuilder('contact');

// Adds a join and returns an alias of added join
DoctrineUtils::joinOnce($qb, 'contact.user', 'u1'); // "u1"

// If a join with specified parameters exists then only returns an alias of existed join
DoctrineUtils::joinOnce($qb, 'contact.user', 'u2'); // "u1"
```

### Get a target class name of a given association path recursively (e.g. "contact.user")
```php
$doctrineUtils->getAssociationTargetClassRecursive('AppBundle/Entity/Company', 'contact.user'); // 'AppBundle/Entity/User'
```

## Symfony ExpressionLanguage utils

### Create an ExpressionFunction from a callable
```php
ExpressionFunctionUtils::fromCallable('Foo\Bar::baz'); // object(ExpressionFunction)
```

For example, this can be useful for injecting simple objects (like ValueObject) into a Symfony service container
```php
class AppExtension extends Extension
{
    #[Override]
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->addExpressionLanguageProvider(new class implements ExpressionFunctionProviderInterface {
            public function getFunctions(): array {
                return [ExpressionFunctionUtils::fromCallable([WalletIdentifier::class, 'create'], 'walletId')];
            }
        });

        $container
            ->getDefinition(OrderService::class)
            ->setArgument('$wallet', expr('walletId(13)'));
    }
}
```

## Symfony Forms utils

### Convert domain model constraint violation to the form constraint violation
It's maybe useful when you validate your model from form on the domain layer and want to map violations to the form.
```php
use Cosmologist\Gears\Symfony\Form\FormUtils;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Validator\Exception\ValidationFailedException;

if ($form->isSubmitted()) {
     try {
         return $this->handler->create($form->getData());
     } catch (ValidationFailedException $exception) {
         $violationMapper = new ViolationMapper();
         foreach ($exception->getViolations() as $domainViolation) {
             $violationMapper->mapViolation(FormUtils::convertDomainViolationToFormViolation($domainViolation), $form);
         }
     }
}

return $form->createView();
```

### Trait with a method implementing DataMapperInterface::mapDataToForms with default behavior
This is convenient for mapping of form data to a model via _DataMapperInterface::mapFormsToData()_,
for example, to create a model via a constructor,
in this case, the mapping of model data to a form via _DataMapperInterface::mapDataToForms()_ will remain unchanged,
and you cannot not define it, since it is required by the _DataMapperInterface_.
```php
use Cosmologist\Gears\Symfony\Form\DataFormsMapperDefaultTrait;

class TransactionFormType extends AbstractType implements DataMapperInterface
{
    use DataFormsMapperDefaultTrait;

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->setDataMapper($this);
    }

    #[Override]
    public function mapFormsToData(Traversable $forms, mixed &$viewData): void
    {
        $forms = iterator_to_array($forms);
        $viewData = new Contact($forms['name']->getData());
    }
```
## Symfony Framework utils

### Configure your Symfony application as a bundle using service container extension and configuration
```php
namespace App;

use App\DependencyInjection\AppExtension;
use Cosmologist\Gears\Symfony\Framework\AppExtension\AppExtensionKernelInterface;
use Cosmologist\Gears\Symfony\Framework\AppExtension\RegisterAppExtensionKernelTrait;
use Override;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel implements AppExtensionKernelInterface
{
    use MicroKernelTrait;
    use RegisterAppExtensionKernelTrait;

    #[Override]
    public function getAppExtension(): ExtensionInterface
    {
        return new AppExtension();
    }
}
```

## Symfony Messenger utils

### Assert that a symfony messenger command (a command bus message) execution will throw an exception
```php
class FooTest extends KernelTestCase
{
    use MessengerTestUtilsTrait;
    
    protected function setUp(): void
    {
        self::bootKernel();
        
        // A MessengerTestUtilsTrait needs your command bus
        $this->commandBus = $this->getContainer()->get('command.bus');;
    }
    
    public function testBar() {
        $this->assertCommandShouldFail(new FooCommand);
        $this->assertCommandShouldFail(new FooCommand, BarException::class);
    }
}
```

### Symfony Messenger transport to redispatch messages on kernel.terminate event

It's a convenient way to speed up your app response to clients by scheduling hard tasks after the server response,
thanks to the kernel.terminate event.

Firstly, you should enable this transport:
```yaml
# config/services.yaml
services:
    _defaults:
        autoconfigure: true

    Cosmologist\Gears\Symfony\Messenger\Transport\KernelTerminate\KernelTerminateTransportFactory:
```

Configure a messenger:
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
    
        transports:
            terminate: symfony-kernel-terminate://

        routing:
            App\Event\FooEvent: terminate

# Use "sync://" transport instead "symfony-kernel-terminate://" for tests
when@test:
  framework:
    messenger:
      transports:
        terminate: sync://
```

and
```php
$this->messenger->dispatch(new App\Event\FooEvent('bar'));
// or
$this->messengerBus->dispatch(new App\Event\FooEvent('bar'));
```

## Symfony PropertyAccess utils

### Get the values of the property path of the object or of the array recursively
```php
use Cosmologist\Gears\Symfony\PropertyAccessor\RecursivePropertyAccessor;

$grandfather = new Person(name: 'grandfather');
$dad = new Person(name: 'dad', parent: $grandfather);
$i = new Person(name: 'i', parent: $dad);

(new RecursivePropertyAccessor())->getValue($i, 'parent'); // [Person(dad), Person(grandfather)]
```

## Symfony Security utils

### A SuperUserRoleVoter brings a ROLE_SUPER_USER, which effectively bypasses any, and all security checks

#### Enable the ROLE_SUPER_USER
```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        
    Cosmologist\Gears\Symfony\Security\Voter\SuperUserRoleVoter:
```

#### Check if ROLE_SUPER_USER granted (e.g. inside a controller)
```php
class FooController extends AbstractController
{
    public function barAction(): Response
    {
        $this->denyAccessUnlessGranted(SuperUserRoleVoter::ROLE_SUPER_USER);
        ...
    }
}
```

## Symfony Validator utils

### Simple and convenient way instance of ValidationFailedException with single ConstraintViolation
```php
use Cosmologist\Gears\Symfony\Validator\ValidationFailedException;

ValidationFailedException::violate($foo, "Foo with invalid bar");
ValidationFailedException::violate($foo, "Foo with invalid {{ bar }}", compact('bar'));
ValidationFailedException::violate($foo, "Foo with invalid bar", propertyPath: 'bar');
```
