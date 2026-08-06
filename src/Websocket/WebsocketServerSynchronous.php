<?php

namespace Cosmologist\Gears\Websocket;

use Cosmologist\Gears\Websocket\Exception\WebsocketServerException;

class WebsocketServerSynchronous
{
    private $serverSocket;
    private $clientSocket;

    /**
     * Start the server and block execution until a client connects.
     *
     * @param int $port    Port for the websocket server
     * @param int $timeout Timeout waiting for connection in seconds
     *
     * @throws WebsocketServerException
     */
    public function listen(int $port = 8080, int $timeout = 180): void
    {
        $address = "tcp://127.0.0.1:{$port}";
        $this->serverSocket = stream_socket_server($address, $errno, $errstr);

        if (!$this->serverSocket) {
            throw WebsocketServerException::connectionFailed(sprintf('%s (%d)', $errstr, $errno));
        }

        // Wait for connection (blocking call)
        $read = [$this->serverSocket];
        $write = null;
        $except = null;
        
        if (stream_select($read, $write, $except, $timeout) > 0) {
            $this->clientSocket = stream_socket_accept($this->serverSocket);
            $this->performHandshake();
        } else {
            throw WebsocketServerException::connectionTimeout($timeout);
        }
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
     * Pack string into WebSocket Text Frame (unmasked) and send.
     * 
     * @param string $payload
     * @throws WebsocketServerException
     */
    public function send(string $payload): void
    {
        if (!$this->clientSocket) {
            throw WebsocketServerException::noClientConnected();
        }

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
     * @return string
     * @throws WebsocketServerException
     */
    public function receive(): string
    {
        if (!$this->clientSocket) {
            throw WebsocketServerException::noClientConnected();
        }

        while (true) {
            $header = fread($this->clientSocket, 2);
            if (!$header || strlen($header) !== 2) {
                throw WebsocketServerException::connectionClosed();
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
                    throw WebsocketServerException::connectionClosed();
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
                throw WebsocketServerException::connectionClosed();
            }

            // We assume Text Frame (0x01) or Continuation (0x00) for normal responses
            return $payload;
        }
    }

    /**
     * Read HTTP headers from client and reply with WebSocket key.
     * 
     * @throws WebsocketServerException
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
            throw WebsocketServerException::invalidHandshake();
        }
    }
}
