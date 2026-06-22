<?php

namespace Cosmologist\Gears;

final class NetworkException extends \RuntimeException
{
    public static function unableToServe(string $address, ?string $error = null, ?int $errorCode = null): self
    {
        $message = "Unable to serve file on '{$address}'";

        if ($error !== null && $error !== '') {
            $message .= ": {$error}";
        }

        if ($errorCode !== null && $errorCode !== 0) {
            $message .= " (code: {$errorCode})";
        }

        return new self($message);
    }

    public static function invalidIp(string $ip, bool $invalidIpV4, bool $invalidIpV6): self
    {
        $type = match (true) {
            $invalidIpV4 && $invalidIpV6 => 'neither IPv4 nor IPv6',
            $invalidIpV4 => 'not IPv4',
            $invalidIpV6 => 'not IPv6',
            default => 'invalid IP',
        };

        return new self("Value '{$ip}' is {$type}");
    }
}
