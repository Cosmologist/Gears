<?php

namespace Cosmologist\Gears\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class GuzzleBuilder
{
    private array $options = [];

    /**
     * Configure Guzzle options to simulate a browser.
     */
    public function configureAsBrowser(): self
    {
        $this->useTimeout(15.0);
        $this->useConnectTimeout(5.0);
        $this->useCookies();
        $this->allowRedirects();

        $this->options['headers'] = [
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
        ];

        return $this;
    }

    /**
     * Set timeout option.
     */
    public function useTimeout(float $timeout): self
    {
        $this->options['timeout'] = $timeout;

        return $this;
    }

    /**
     * Set connect timeout option.
     */
    public function useConnectTimeout(float $connectTimeout): self
    {
        $this->options['connect_timeout'] = $connectTimeout;

        return $this;
    }

    /**
     * Enable cookie jar.
     */
    public function useCookies(): self
    {
        $this->options['cookies'] = new CookieJar();

        return $this;
    }

    /**
     * Configure redirect handling.
     */
    public function allowRedirects(
        int $max = 5,
        bool $strict = false,
        bool $referer = true,
        bool $trackRedirects = true
    ): self {
        $this->options['allow_redirects'] = [
            'max' => $max,
            'strict' => $strict,
            'referer' => $referer,
            'track_redirects' => $trackRedirects,
        ];

        return $this;
    }

    /**
     * Configure Guzzle to bind to a specific network interface.
     */
    public function bindToInterface(?string $interface = null): self
    {
        // Ensure Guzzle is using cURL handler
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('cURL extension is required for interface binding');
        }

        // Auto-detect interface if not provided
        if ($interface === null) {
            $interface = $this->detectPhysicalInterface();
        }

        $this->options['curl'] = [
            CURLOPT_INTERFACE => $interface,
        ];

        return $this;
    }

    /**
     * Detect first available physical network interface.
     */
    private function detectPhysicalInterface(): string
    {
        // Get all network interfaces
        $interfaces = net_get_interfaces();

        if (empty($interfaces)) {
            throw new \RuntimeException('No network interfaces found');
        }

        // Standard predictable interface prefixes (https://www.thomas-krenn.com/en/wiki/Predictable_Network_Interface_Names)
        $standardPrefixes = ['en', 'ib', 'sl', 'wl', 'ww'];

        // Legacy prefixes
        $legacyPrefixes = ['eth', 'wlan'];

        // Combined list of all valid prefixes
        $validPrefixes = array_merge($standardPrefixes, $legacyPrefixes);

        // Filter physical interfaces
        foreach ($interfaces as $name => $interface) {
            // Skip loopback and virtual interfaces
            if (str_starts_with($name, 'lo')) {
                continue;
            }

            // Interface is actively running and allocated by the system.
            if (empty($interface['flags']) || !($interface['flags'] & 0x1)) {
                continue;
            }

            // Check if interface name starts with any valid prefix
            foreach ($validPrefixes as $prefix) {
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
    public function create(): Client
    {
        return new Client($this->options);
    }
}
