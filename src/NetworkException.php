<?php

namespace Cosmologist\Gears;

final class NetworkException extends \RuntimeException
{
    public static function invalidIp(string $ip): self
    {
        return new self("Value '{$ip}' is not a valid IP address");
    }
}
