<?php

namespace Cosmologist\Gears\Browser;

use Cosmologist\Gears\Browser\Exception\BrowserControlException;
use Cosmologist\Gears\Websocket\WebsocketServerSynchronous;

class BrowserControl
{
    public const DEFAULT_PORT = 42778;
    public const DEFAULT_TIMEOUT = 180;

    private $server;
    private float $lastNavigateTimestamp = 0.0;

    public function __construct()
    {
        $this->server = new WebsocketServerSynchronous();
    }

    /**
     * Start the server and block execution until the browser extension connects.
     *
     * @param int $port    Port for the websocket server
     * @param int $timeout Timeout waiting for connection in seconds
     *
     * @throws \Cosmologist\Gears\Websocket\Exception\WebsocketServerException
     */
    public function connect(int $port = self::DEFAULT_PORT, int $timeout = self::DEFAULT_TIMEOUT): self
    {
        $this->server->listen($port, $timeout);
        
        return $this;
    }

    /**
     * Navigate to URL.
     * 
     * @param string $url
     * @param float $delay Pause between navigate calls in seconds. If the next call happens sooner, it will block execution until the delay expires.
     * @return self
     */
    public function navigate(string $url, float $delaySec = 0.0): BrowserControl
    {
        if ($delaySec > 0 && $this->lastNavigateTimestamp > 0) {
            $elapsedSec = microtime(true) - $this->lastNavigateTimestamp;
            if ($elapsedSec < $delaySec) {
                usleep((int) (($delaySec - $elapsedSec) * 1000000));
            }
        }

        $this->command('navigate', ['url' => $url]);
        $this->lastNavigateTimestamp = microtime(true);

        return $this;
    }

    /**
     * Get HTML of the page.
     * 
     * @return string|null
     */
    public function html(): ?string
    {
        return $this->command('html');
    }

    /**
     * Execute javascript code in the page.
     * 
     * @param string $code
     */
    public function execute(string $code): array|string|int|float|bool|null
    {
        return $this->command('execute', ['code' => $code]);
    }

    /**
     * Select elements matching CSS selector.
     * 
     * @param string $selector
     * @return array
     */
    public function select(string $selector): array
    {
        return $this->command('select', ['selector' => $selector]);
    }

    /**
     * Disconnect the client and stop the server.
     */
    public function disconnect(): self
    {
        $this->server->disconnect();

        return $this;
    }

    /**
     * Send command and read response synchronously.
     * 
     * @param string $method
     * @param array $params
     * @return array|string|int|float|bool|null
     * @throws BrowserControlException
     * @throws \Cosmologist\Gears\Websocket\Exception\WebsocketServerException
     */
    private function command(string $method, array $params = []): array|string|int|float|bool|null
    {
        $payload = ['cmd' => $method];
        if (!empty($params)) {
            $payload['args'] = $params;
        }

        $this->server->send(json_encode($payload));

        $responseJson = $this->server->receive();
        $response = json_decode($responseJson, true);
        if ($response === null) throw new \RuntimeException("JSON decode error: " . json_last_error_msg() . " (payload length: " . strlen($responseJson) . ")");

        if (isset($response['error_type'])) {
            throw BrowserControlException::extensionError(
                $response['error_type'], 
                $response['error_message']
            );
        }

        return $response['items'] ?? $response['value'] ?? null;
    }
}
