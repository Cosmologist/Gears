<?php

namespace Cosmologist\Gears\Websocket\Exception;

use RuntimeException;

class WebsocketServerException extends RuntimeException
{
    public static function connectionFailed(string $error): self
    {
        return new self("WebSocket Server Connection failed: {$error}");
    }

    public static function connectionTimeout(int $timeout): self
    {
        return new self("WebSocket Server Connection timeout after {$timeout} seconds");
    }

    public static function noClientConnected(): self
    {
        return new self('No client connected to WebSocket Server');
    }

    public static function invalidHandshake(): self
    {
        return new self('Invalid WebSocket handshake request');
    }

    public static function connectionClosed(): self
    {
        return new self('WebSocket connection closed by peer');
    }
}
