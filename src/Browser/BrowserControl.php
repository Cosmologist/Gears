<?php

namespace Cosmologist\Gears\Browser;

use Cosmologist\Gears\Browser\Exception\BrowserControlException;
use Cosmologist\Gears\Websocket\WebsocketServerSynchronous;

class BrowserControl
{
    private $server;

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
    public function listen(int $port = 8080, int $timeout = 180): void
    {
        $this->server->listen($port, $timeout);
    }

    /**
     * Navigate to URL.
     * 
     * @param string $url
     */
    public function navigate(string $url): void
    {
        $this->sendCommand('navigate', ['url' => $url]);
    }

    /**
     * Get HTML of the page.
     * 
     * @return string
     */
    public function getHtml(): string
    {
        $result = $this->sendCommand('html');
        return $result['html'] ?? '';
    }

    /**
     * Execute javascript code in the page.
     * 
     * @param string $code
     * @return mixed
     */
    public function execute(string $code)
    {
        $result = $this->sendCommand('execute', ['code' => $code]);
        return $result['value'] ?? null;
    }

    /**
     * Select elements matching CSS selector.
     * 
     * @param string $selector
     * @return array
     */
    public function select(string $selector): array
    {
        $result = $this->sendCommand('select', ['selector' => $selector]);
        return $result['items'] ?? [];
    }

    /**
     * Disconnect the client and stop the server.
     */
    public function disconnect(): void
    {
        $this->server->disconnect();
    }

    /**
     * Send command and read response synchronously.
     * 
     * @param string $method
     * @param array $params
     * @return array
     * @throws BrowserControlException
     * @throws \Cosmologist\Gears\Websocket\Exception\WebsocketServerException
     */
    private function sendCommand(string $method, array $params = []): array
    {
        $payload = ['cmd' => $method];
        if (!empty($params)) {
            $payload['args'] = $params;
        }

        $this->server->send(json_encode($payload));

        $responseJson = $this->server->receive();
        $response = json_decode($responseJson, true);

        if (isset($response['error_type'])) {
            throw BrowserControlException::extensionError(
                $response['error_type'], 
                $response['error_message'] ?? 'Unknown error'
            );
        }

        return $response ?: [];
    }
}
