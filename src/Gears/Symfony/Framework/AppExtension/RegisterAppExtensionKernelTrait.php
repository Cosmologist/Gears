<?php

namespace Cosmologist\Gears\Symfony\Framework\AppExtension;

use Override;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationParameterBag;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Configure your Symfony application as a bundle using service container extension and configuration files
 *
 * How to Use:
 * 1. Implement Extension, for example, in `src/DependencyInjection/AppExtension.php`.
 * 2. Include this trait in your kernel (`src/Kernel.php`).
 * 3. Implement the `AppExtensionKernelInterface` in your kernel.
 * 4. Implement the `getAppExtension()` method in your kernel, which returns an instance of `AppExtension`.
 *
 * Example:
 * <code>
 * namespace App;
 *
 * use App\DependencyInjection\AppExtension;
 * use Cosmologist\Gears\Symfony\Framework\AppExtension\AppExtensionKernelInterface;
 * use Cosmologist\Gears\Symfony\Framework\AppExtension\RegisterAppExtensionKernelTrait;
 * use Override;
 * use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
 * use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
 * use Symfony\Component\HttpKernel\Kernel as BaseKernel;
 *
 * class Kernel extends BaseKernel implements AppExtensionKernelInterface
 * {
 *     use MicroKernelTrait;
 *     use RegisterAppExtensionKernelTrait;
 *
 *     #[Override]
 *     public function getAppExtension(): ExtensionInterface
 *     {
 *         return new AppExtension();
 *     }
 * }
 * </code>
 *
 * <h5>A. How to Symfony process the service container extensions</h5>
 * 1. When building the container, {@link MicroKernelTrait::configureContainer()} is called, which loads `config/packages/*`, `config/services*`, etc.
 *    a. For the configuration namespace (root element of the configuration) in the kernel, a similarly named extension must be registered;
 *       otherwise, an error will occur, see, for example, {@link YamlFileLoader::validate()}.
 * 2. System CompilerPasses are executed, with the main one being {@link MergeExtensionConfigurationPass}.
 *    a. `MergeExtensionConfigurationPass` iterates over all registered extensions.
 *       - For each, it creates a temporary container (to isolate extensions from each other).
 *       - It delegates control to the extension via {@link ExtensionInterface::load()}, which populates the temporary container.
 *       - The temporary container is merged into the main container.
 *    b. Application-level services loaded in step 1 are merged into the main container.
 *       This ensures they have priority (you can override bundle services at the application level).
 *
 * <h5>Problem 1</h5>
 * If you simply register `AppExtension` via {@link ContainerBuilder::registerExtension()},
 * it will not see application-level services (see point A.2.a), so `$container->getDefinition('App\Service')` will throw an exception,
 * and application-level services will always override services declared in the extension (see point A.2.b).
 * The only exception is if you do not use autowiring in `config/services.yaml`; then you can define new services in `AppExtension`.
 *
 * <h6>Solution Used</h6>
 * Pass control to `AppExtension` during container building and pass the main container to it, not a temporary one, see {@link RegisterAppExtensionKernelTrait::prepareContainer()}.
 * This way, `AppExtension` can see application-level services, and changes it makes to the container will not be overwritten.
 *
 * <h5>Problem 2</h5>
 * If you do not register `AppExtension` via {@link ContainerBuilder::registerExtension()},
 * there will be an exception about the absence of a registered extension for the configuration namespace when loading the configuration for `AppExtension` (see point A.1.a).
 *
 * <h6>Solution Used</h6>
 * Register a dummy extension, see {@link RegisterAppExtensionKernelTrait::prepareContainer()}, which does nothing but has the corresponding alias, the same as the configuration namespace.
 * This ensures that the corresponding configuration passes the validation described above and is accessible via `$container->getExtensionConfig($appExtension->getAlias())`,
 * from where it is retrieved and passed to `AppExtension` in the {@link RegisterAppExtensionKernelTrait::buildContainer()} method.
 *
 * <i>Yes, yes, yes, the solution with a dummy extension is not very elegant.</i>
 */
trait RegisterAppExtensionKernelTrait
{
    /**
     * Here, we register a dummy extension in the container
     * so that the extension configuration gets into the container and does not cause an error.
     */
    #[Override]
    protected function prepareContainer(ContainerBuilder $container): void
    {
        $this->validate();

        $container->registerExtension(new class($this->getAppExtension()->getAlias()) extends Extension {
            public function __construct(private readonly string $alias)
            {
            }

            public function getAlias(): string
            {
                return $this->alias;
            }

            public function load(array $configs, ContainerBuilder $container)
            {
            }
        });

        parent::prepareContainer($container);
    }

    /**
     * Here, we launch `AppExtension`.
     */
    #[Override]
    protected function buildContainer(): ContainerBuilder
    {
        $container = parent::buildContainer();

        /**
         * {@link MergeExtensionConfigurationPass} emulation
         */
        $appExtension          = $this->getAppExtension();
        $appExtensionAlias     = $appExtension->getAlias();
        // Resolve parameters placeholders in the extension configuration
        $appExtensionConfigRaw = $container->getExtensionConfig($appExtensionAlias);
        $configResolver        = new MergeExtensionConfigurationParameterBag($container->getParameterBag());
        $appExtensionConfigResolved = $configResolver->resolveValue($appExtensionConfigRaw);

        $appExtension->load($appExtensionConfigResolved, $container);

        return $container;
    }

    /**
     * Validate that the kernel, to which the trait is applied, implements `AppExtensionKernelInterface`.
     */
    private function validate(): void
    {
        if (!$this instanceof AppExtensionKernelInterface) {
            throw new RuntimeException(sprintf('Kernel should implement "%s" to apply "%s"', AppExtensionKernelInterface::class, __TRAIT__));
        }
    }
}
