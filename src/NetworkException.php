<?php

namespace Cosmologist\Gears;

final class NetworkException extends \RuntimeException
{
    public static function unableToServe(string $address, string $reason): self
    {
        return new self("Unable to serve file on '{$address}': {$reason}");
    }

    public static function invalidIp(string $ip): self
    {
        return new self("Value '{$ip}' is not a valid IP address");
    }
}
