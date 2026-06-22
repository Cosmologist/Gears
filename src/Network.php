<?php

namespace Cosmologist\Gears;

final class Network
{
    /**
     * Check if the value is a valid IP address
     */
    public static function isIp(string $ip, bool $allowV4 = true, bool $allowV6 = true): bool
    {
        $flags = 0;

        if ($allowV4 && !$allowV6) {
            $flags = FILTER_FLAG_IPV4;
        } elseif (!$allowV4 && $allowV6) {
            $flags = FILTER_FLAG_IPV6;
        } elseif (!$allowV4 && !$allowV6) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false;
    }

    /**
     * Assert that the value is a valid IP address
     *
     * @throws NetworkException If the value is not a valid IP address
     */
    public static function assertIp(string $ip, bool $allowV4 = true, bool $allowV6 = true): void
    {
        if (!self::isIp($ip, $allowV4, $allowV6)) {
            throw NetworkException::invalidIp($ip);
        }
    }
}
