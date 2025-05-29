<?php

namespace Cosmologist\Gears\Symfony\Framework\AppExtension;

use Override;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Конфигурируйте Symfony-приложение как бандл - используя Container Extension и файлы конфигурации.
 *
 * Как использовать:
 * 1. Реализуйте Extension, например, в src/DependencyInjection/AppExtension.php.
 * 2. Подключите этот трейт к вашему ядру (src/Kernel.php).
 * 3. Подключите к ядру интерфейс AppExtensionKernelInterface.
 * 4. Реализуйте в ядре метод getAppExtension() этого интерфейса, который вернет инстанц AppExtension.
 *
 * Пример:
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
 * <h5>A. Важные моменты работы Dependency Injection в Symfony</h5>
 * 1. При сборке контейнера вызывается {@link MicroKernelTrait::configureContainer()}, который загружает config/packages/*, config/services*, etc.
 *    a. Для namespace конфигурации (корневого элемент конфигурации) в ядре должен быть зарегистрирован одноименный extension,
 *       иначе будет ошибка, смотри, например {@link YamlFileLoader::validate()}.
 * 2. Прогоняются системные CompilerPass'ы, основным является {@link MergeExtensionConfigurationPass}.
 *    a. MergeExtensionConfigurationPass перебирает все зарегистрированные extension.
 *       - Для каждого создает временный контейнер (чтобы изолировать extension между собой).
 *       - Передает управление в extension через {@link ExtensionInterface::load()}, который наполняет временный контейнер.
 *       - Временный контейнер вмерживается в основной.
 *    b. Сервисы уровня приложения, загруженные в пункте 1, вмерживаются в основной контейнер.
 *       Это нужно, чтобы они имели приоритет (можно было переопределить сервис из бандла на уровне приложения).
 *
 * <h5>Проблема 1</h5>
 * Если просто зарегистрировать AppExtension, через {@link ContainerBuilder::registerExtension()},
 * то он не будет видеть сервисы уровня приложения (смотри пункт A.2.a), то есть ```$container->getDefinition('App\Service')``` выбросит исключение,
 * a cервисы уровня приложения будут всегда переписывать сервисы объявленные в extension (смотри пункт A.2.b).
 * Единственное исключение если вы не используете autowiring в config/services.yaml, тогда да - возможность определять новые сервисы в AppExtension будет.
 *
 * <h6>Используемое решение</h6>
 * Передать управление в AppExtension при сборке контейнера и передать в него основной контейнер, а не временный, смотри {@link RegisterAppExtensionKernelTrait::prepareContainer()}.
 * Благодаря этому AppExtension видит сервисы уровня приложения и изменения, которые он внесет в контейнер не затрутся.
 *
 * <h5>Проблема 2</h5>
 * Если не регистрировать AppExtension, через {@link ContainerBuilder::registerExtension()},
 * то при загрузке конфигурации для AppExtension будет исключение о отсутствии зарегистрированного extension для namespace конфигурации (смотри пункт A.1.a).
 *
 * <h6>Используемое решение</h6>
 * Регистрируется пустой extension, смотри {@link RegisterAppExtensionKernelTrait::prepareContainer()}, который ничего не делает,
 * лишь имеет соответствующий алиас, одноименный namespace конфигурации.
 * Благодаря ему, соответсвующая конфигурация проходит проверку описанную выше, и доступна через ```$container->getExtensionConfig($appExtension->getAlias())```,
 * откуда ее и достаем передавая управление в AppExtension в методе {@link RegisterAppExtensionKernelTrait::buildContainer()}.
 *
 * <i>Да-да-да, решение c extension-заглушкой не очень эстетичное.</i>
 */
trait RegisterAppExtensionKernelTrait
{
    /**
     * Здесь регистрируем в контейнере extension-заглушку для того,
     * чтобы конфигурация extension попадала в контейнер и не приводила к ошибке.
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
     * Здесь запускаем AppExtension
     */
    #[Override]
    protected function buildContainer(): ContainerBuilder
    {
        $container    = parent::buildContainer();
        $appExtension = $this->getAppExtension();
        $appExtension->load($container->getExtensionConfig($appExtension->getAlias()), $container);

        return $container;
    }

    /**
     * Проверяем что ядро, куда подключен trait, реализует AppExtensionKernelInterface
     */
    private function validate(): void
    {
        if (!$this instanceof AppExtensionKernelInterface) {
            throw new RuntimeException(sprintf('Kernel should implement "%s" for apply "%s"', AppExtensionKernelInterface::class, __TRAIT__));
        }
    }
}
