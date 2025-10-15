<?php

namespace Cosmologist\Gears\Symfony\ExpressionLanguage;

use Cosmologist\Gears\CallableType;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

class ExpressionFunctionUtils
{
    /**
     * Create an ExpressionFunction from a callable
     *
     * <code>
     * ExpressionFunctionUtils::fromCallable('Foo\Bar::baz'); // object(ExpressionFunction)
     * </code>
     *
     * For example, this can be useful for injecting simple objects (like ValueObject) into a Symfony service container
     * <code>
     * class AppExtension extends Extension
     * {
     *     #[Override]
     *     public function load(array $configs, ContainerBuilder $container)
     *     {
     *         $container->addExpressionLanguageProvider(new class implements ExpressionFunctionProviderInterface {
     *             public function getFunctions(): array {
     *                 return [ExpressionFunctionUtils::fromCallable([WalletIdentifier::class, 'create'], 'walletId')];
     *             }
     *         });
     *
     *         $container
     *             ->getDefinition(OrderService::class)
     *             ->setArgument('$wallet', expr('walletId(13)'));
     *     }
     * }
     * </code>
     *
     * @param callable $callable Only a callable of function or static method supports
     * @param string $name The expression function name (default: same than the PHP function name)
     */
    public static function fromCallable(callable $callable, ?string $name = null): ExpressionFunction
    {
        if (!CallableType::isFunction($callable) && !CallableType::isStaticMethod($callable)) {
            throw new InvalidArgumentException('Only a callable of function or static method supports');
        }

        $reflection = CallableType::reflection($callable);

        if ($reflection instanceof ReflectionMethod) {
            return new ExpressionFunction($name ?? $reflection->name, function (...$functionArgs) use ($reflection) {
                return sprintf('\%s::%s(%s)', ltrim($reflection->class, '\\'), $reflection->name, implode(", ", $functionArgs));
            }, function ($evaluatorArgs, ...$functionArgs) use($callable) {
                return forward_static_call($callable, ...$functionArgs);
            });
        }

        if ($reflection instanceof ReflectionFunction) {
            return new ExpressionFunction($name ?? $reflection->name, function (...$functionArgs) use ($reflection) {
                return sprintf('\%s(%s)', ltrim($reflection->name, '\\'), implode(", ", $functionArgs));
            }, function ($evaluatorArgs, ...$functionArgs) use($callable) {
                return call_user_func($callable, ...$functionArgs);
            });
        }
    }
}
