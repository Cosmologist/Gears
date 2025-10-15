<?php

namespace Cosmologist\Gears\Symfony\Messenger\Tests;

use Exception;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

trait MessengerTestUtilsTrait
{
    /**
     * Occur that a commandBus was initialized (with setUp method, for example):
     * <code>
     * $this->commandBus = self::getContainer()->get('command.bus');
     * </code>
     */
    private MessageBusInterface $commandBus;

    /**
     * Assert that a symfony messenger command (command bus message) execution will throw an exception
     *
     * <code>
     * $this->assertCommandShouldFail(new FooCommand);
     * $this->assertCommandShouldFail(new FooCommand, BarException::class);
     * </code>
     */
    private function assertCommandShouldFail(object $command, ?string $withSpecificException = null)
    {
        try {
            $this->commandBus->dispatch($command);
            $this->fail(sprintf('"%s" should be fail', $command::class));
        } catch (AssertionFailedError $phpunitFailException) {
            // This a PHPUnit exception caused with $this->fail() (see above) - we should pass it
            throw  $phpunitFailException;
        } catch (HandlerFailedException $e) {
            if ($withSpecificException === null) {
                $this->assertTrue(true);
            } elseif ($e->getPrevious()::class === $withSpecificException) {
                $this->assertTrue(true);
            }
        } catch (Exception $exception) {
            if ($withSpecificException === null) {
                $this->assertTrue(true);
            } elseif ($e::class === $withSpecificException) {
                $this->assertTrue(true);
            }

            throw $exception;
        }
    }
}
