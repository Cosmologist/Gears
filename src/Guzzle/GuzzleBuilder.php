<?php

namespace Cosmologist\Gears\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class GuzzleBuilder
{
    private static array $options = [];

    /**
     * Configure Guzzle options to simulate a browser.
     */
    public static function configureAsBrowser(): void
    {
        $cookieJar = new CookieJar();

        self::$options = [
            'timeout' => 15.0,
            'connect_timeout' => 5.0,
            'cookies' => $cookieJar,
            'allow_redirects' => [
                'max' => 5,
                'strict' => false,
                'referer' => true,
                'track_redirects' => true,
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Cache-Control' => 'max-age=0',
            ],
        ];
    }

    /**
     * Configure Guzzle to bind to a specific network interface.
     */
    public static function bindToInterface(?string $interface = null): void
    {
        // Ensure Guzzle is using cURL handler
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('cURL extension is required for interface binding');
        }

        // Auto-detect interface if not provided
        if ($interface === null) {
            $interface = self::detectPhysicalInterface();
        }

        self::$options['curl'] = [
            CURLOPT_INTERFACE => $interface,
        ];
    }

    /**
     * Detect first available physical network interface.
     */
    private static function detectPhysicalInterface(): string
    {
        // Get all network interfaces
        $interfaces = net_get_interfaces();

        if (empty($interfaces)) {
            throw new \RuntimeException('No network interfaces found');
        }

        // Common physical interface prefixes
        $physicalPrefixes = ['eth', 'en', 'wlan', 'wlp'];

        // Filter physical interfaces
        foreach ($interfaces as $name => $interface) {
            // Skip loopback and virtual interfaces
            if (str_starts_with($name, 'lo')) {
                continue;
            }

            // Check if interface name starts with physical prefix
            foreach ($physicalPrefixes as $prefix) {
                if (str_starts_with($name, $prefix)) {
                    return $name;
                }
            }
        }

        throw new \RuntimeException('No physical network interface found');
    }

    /**
     * Create and return a Guzzle client with the configured options.
     */
    public static function create(): Client
    {
        return new Client(self::$options);
    }
}
