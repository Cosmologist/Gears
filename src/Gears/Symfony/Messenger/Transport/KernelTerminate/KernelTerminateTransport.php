<?php

namespace Cosmologist\Gears\Symfony\Messenger\Transport\KernelTerminate;

use Override;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Symfony Messenger transport to redispatch messages on kernel.terminate event
 *
 * It's a convenient way to speed up your app response to clients by scheduling hard tasks after the server response,
 * thanks to the kernel.terminate event.
 *
 * Firstly, you should enable this transport:
 * <code>
 * # config/services.yaml
 * services:
 *     _defaults:
 *         autoconfigure: true
 *
 *     Cosmologist\Gears\Symfony\Messenger\Transport\KernelTerminate\KernelTerminateTransportFactory:
 * </code>
 * <code>
 * # config/packages/messenger.yaml
 * framework:
 *     messenger:
 *         transports:
 *             terminate: symfony-kernel-terminate://
 * </code>
 *
 * Then, you should define a rule to route messages to this transport:
 * <code>
 * # config/packages/messenger.yaml
 * framework:
 *     messenger:
 *         routing:
 *             App\Event\FooEvent: terminate
 * </code>
 *
 * and
 * <code>
 * $this->messenger->dispatch(new App\Event\FooEvent('bar'));
 * // or
 * $this->messengerBus->dispatch(new App\Event\FooEvent('bar'));
 * </code>
 */
class KernelTerminateTransport implements TransportInterface
{
    /** @var Envelope[] */
    private array $queue = [];

    public function __construct(private readonly ContainerInterface $busLocator)
    {
    }

    #[Override]
    public function get(): iterable
    {
        throw new InvalidArgumentException('You cannot receive messages from the Messenger KernelTerminateTransport.');
    }

    #[Override]
    public function ack(Envelope $envelope): void
    {
        throw new InvalidArgumentException('You cannot call ack() on the Messenger KernelTerminateTransport.');
    }

    #[Override]
    public function reject(Envelope $envelope): void
    {
        throw new InvalidArgumentException('You cannot call reject() on the Messenger KernelTerminateTransport.');
    }

    #[Override]
    public function send(Envelope $envelope): Envelope
    {
        if (null === $busNameStamp = $envelope->last(BusNameStamp::class)) {
            throw new InvalidArgumentException('Envelope is missing a BusNameStamp.');
        }

        return $this->queue[] = $envelope;
    }

    public function onKernelTerminate(): void
    {
        while (null !== $envelope = array_shift($this->queue)) {
            $busNameStamp  = $envelope->last(BusNameStamp::class);
            $sentStamp     = $envelope->last(SentStamp::class);
            $transportName = $sentStamp?->getSenderAlias() ?? $sentStamp?->getSenderClass() ?? 'kernel-terminate';

            // Dispatch an envelope with a ReceivedStamp to avoid routing to the transport again
            $this
                ->getMessageBus($busNameStamp->getBusName())
                ->dispatch($envelope->with(new ReceivedStamp($transportName)));
        }
    }

    private function getMessageBus(string $busName): MessageBusInterface
    {
        if (!$this->busLocator->has($busName)) {
            throw new InvalidArgumentException(sprintf('Bus named "%s" does not exist.', $busName));
        }

        return $this->busLocator->get($busName);
    }
}
