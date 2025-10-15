<?php

namespace Cosmologist\Gears\Symfony\Messenger\Transport\KernelTerminate;

use Override;
use Psr\Container\ContainerInterface;
use SensitiveParameter;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @see https://github.com/symfony/symfony/pull/28746
 * @see https://github.com/symfony/symfony/issues/28646
 */
readonly class KernelTerminateTransportFactory implements TransportFactoryInterface
{
    public function __construct(
        #[AutowireLocator('messenger.bus')]
        private ContainerInterface       $busLocator,
        private EventDispatcherInterface $eventDispatcher)
    {
    }

    #[Override]
    public function createTransport(#[SensitiveParameter] string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $transport = new KernelTerminateTransport($this->busLocator);

        $this->eventDispatcher->addListener(KernelEvents::TERMINATE, array($transport, 'onTerminate'));
        /*
         * When run an application from the CLI, the `kernel.terminate` event not generated,
         * in this case the events handled on the `console.terminate` event.
         */
        $this->eventDispatcher->addListener(ConsoleEvents::TERMINATE, array($transport, 'onTerminate'));

        return $transport;
    }

    #[Override]
    public function supports(#[SensitiveParameter] string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'symfony-kernel-terminate://');
    }
}
