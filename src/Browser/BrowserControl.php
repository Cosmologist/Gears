<?php

namespace Cosmologist\Gears\Browser;

use Cosmologist\Gears\Browser\Exception\BrowserControlException;

class BrowserControl
{
    private $serverSocket;
    private $clientSocket;

    /**
     * Start the server and block execution until the browser extension connects.
     *
     * @param int $port    Port for the websocket server
     * @param int $timeout Timeout waiting for connection in seconds
     *
     * @throws BrowserControlException
     */
    public function listen(int $port = 8080, int $timeout = 180): void
    {
        $address = "tcp://127.0.0.1:{$port}";
        $this->serverSocket = stream_socket_server($address, $errno, $errstr);

        if (!$this->serverSocket) {
            throw BrowserControlException::connectionFailed(sprintf('%s (%d)', $errstr, $errno));
        }

        // Wait for connection (blocking call)
        $read = [$this->serverSocket];
        $write = null;
        $except = null;
        
        if (stream_select($read, $write, $except, $timeout) > 0) {
            $this->clientSocket = stream_socket_accept($this->serverSocket);
            $this->performHandshake();
        } else {
            throw BrowserControlException::connectionTimeout($timeout);
        }
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
        if ($this->clientSocket) {
            fclose($this->clientSocket);
            $this->clientSocket = null;
        }
        if ($this->serverSocket) {
            fclose($this->serverSocket);
            $this->serverSocket = null;
        }
    }

    /**
     * Send command and read response synchronously.
     * 
     * @param string $method
     * @param array $params
     * @return array
     * @throws BrowserControlException
     */
    private function sendCommand(string $method, array $params = []): array
    {
        if (!$this->clientSocket) {
            throw BrowserControlException::noClientConnected();
        }

        $payload = ['cmd' => $method];
        if (!empty($params)) {
            $payload['args'] = $params;
        }

        $this->writeFrame(json_encode($payload));

        $responseJson = $this->readFrame();
        $response = json_decode($responseJson, true);

        if (isset($response['error_type'])) {
            throw BrowserControlException::extensionError(
                $response['error_type'], 
                $response['error_message'] ?? 'Unknown error'
            );
        }

        return $response ?: [];
    }

    /**
     * Read HTTP headers from browser and reply with WebSocket key.
     * 
     * @throws BrowserControlException
     */
    private function performHandshake(): void
    {
        $request = fread($this->clientSocket, 2048);
        
        if (preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches)) {
            $key = trim($matches[1]);
            $acceptKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            
            $response = "HTTP/1.1 101 Switching Protocols\r\n"
                      . "Upgrade: websocket\r\n"
                      . "Connection: Upgrade\r\n"
                      . "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";
                      
            fwrite($this->clientSocket, $response);
        } else {
            throw BrowserControlException::invalidHandshake();
        }
    }

    /**
     * Pack string into WebSocket Text Frame (unmasked).
     */
    private function writeFrame(string $payload): void
    {
        $b1 = 0x80 | 0x01; // FIN + Text Frame
        $length = strlen($payload);
        
        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length > 125 && $length < 65536) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            $header = pack('CCNN', $b1, 127, 0, $length);
        }

        fwrite($this->clientSocket, $header . $payload);
    }

    /**
     * Read WebSocket frame from socket and unmask it.
     * 
     * @throws BrowserControlException
     */
    private function readFrame(): string
    {
        while (true) {
            $header = fread($this->clientSocket, 2);
            if (!$header || strlen($header) !== 2) {
                throw BrowserControlException::connectionClosed();
            }

            $b1 = ord($header[0]);
            $b2 = ord($header[1]);

            $opcode = $b1 & 0x0F;
            $isMasked = $b2 & 0x80;
            $payloadLength = $b2 & 0x7F;

            if ($payloadLength === 126) {
                $ext = fread($this->clientSocket, 2);
                $payloadLength = unpack('n', $ext)[1];
            } elseif ($payloadLength === 127) {
                $ext = fread($this->clientSocket, 8);
                $payloadLength = unpack('N2', $ext)[2]; // taking lower 32 bits for simplicity
            }

            $mask = '';
            if ($isMasked) {
                $mask = fread($this->clientSocket, 4);
            }

            $payload = '';
            $remaining = $payloadLength;
            while ($remaining > 0) {
                $chunk = fread($this->clientSocket, min(8192, $remaining));
                if ($chunk === false || strlen($chunk) === 0) {
                    throw BrowserControlException::connectionClosed();
                }
                $payload .= $chunk;
                $remaining -= strlen($chunk);
            }

            if ($isMasked && $payloadLength > 0) {
                $unmaskedPayload = '';
                for ($i = 0; $i < $payloadLength; $i++) {
                    $unmaskedPayload .= $payload[$i] ^ $mask[$i % 4];
                }
                $payload = $unmaskedPayload;
            }

            // Handle Ping
            if ($opcode === 0x09) {
                // Reply with Pong (0xA) using the same payload
                $pongHeader = pack('CC', 0x80 | 0x0A, strlen($payload));
                fwrite($this->clientSocket, $pongHeader . $payload);
                continue; // Wait for the next frame
            }

            // Handle Close
            if ($opcode === 0x08) {
                throw BrowserControlException::connectionClosed();
            }

            // We assume Text Frame (0x01) or Continuation (0x00) for normal responses
            return $payload;
        }
    }
}
