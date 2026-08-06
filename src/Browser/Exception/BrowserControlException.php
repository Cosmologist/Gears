<?php

namespace Cosmologist\Gears\Browser\Exception;

class BrowserControlException extends \RuntimeException
{
    public static function connectionTimeout(int $timeout): self
    {
        return new self(sprintf('Timeout waiting for browser extension to connect after %d seconds', $timeout));
    }

    public static function invalidHandshake(): self
    {
        return new self('Invalid WebSocket handshake');
    }

    public static function connectionClosed(): self
    {
        return new self('Connection closed by client');
    }

    public static function connectionFailed(string $error): self
    {
        return new self(sprintf('Could not start server: %s', $error));
    }

    public static function noClientConnected(): self
    {
        return new self('No client connected');
    }

    public static function extensionError(string $type, string $message): self
    {
        return new self(sprintf('Browser extension returned error "%s": %s', $type, $message));
    }
}
