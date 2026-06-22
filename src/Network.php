<?php

namespace Cosmologist\Gears;

use Uri\Rfc3986\Uri;

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

    /**
     * Start a one-time HTTP server and serve the file once by secret URL
     *
     * The server starts on the given IP and port, generates a unique URL with a hash,
     * passes that URL to the callback, then waits until the matching HTTP request arrives.
     * After the file is fully sent, the server stops and the method returns.
     *
     * @param  callable(string):void  $urlRecipient
     *
     * @throws FileException If the target does not exist, is not a regular file, or cannot be read
     * @throws NetworkException If the server cannot be started
     */
    public static function serve(File $file, callable $urlRecipient, string $ip = '0.0.0.0', int $port = 0): void
    {
        $file->assertFile();

        $address = "tcp://{$ip}:{$port}";
        $errorCode = 0;
        $errorMessage = '';
        $server = stream_socket_server($address, $errorCode, $errorMessage);

        if ($server === false) {
            throw NetworkException::unableToServe($address, $errorMessage ?: "error {$errorCode}");
        }

        try {
            $hash = bin2hex(random_bytes(16));
            [$serverIp, $serverPort] = explode(':', stream_socket_get_name($server, false));

            $url = (new Uri('http://localhost'))
                ->withHost($serverIp)
                ->withPort((int) $serverPort)
                ->withPath('/' . $hash)
                ->toString();

            $urlRecipient($url);

            while (true) {
                $connection = stream_socket_accept($server, -1);

                if ($connection === false) {
                    continue;
                }

                $requestLine = fgets($connection);

                if ($requestLine === false) {
                    fclose($connection);
                    continue;
                }

                while (($headerLine = fgets($connection)) !== false) {
                    if ($headerLine === "\r\n" || $headerLine === "\n") {
                        break;
                    }
                }

                if (!preg_match('#^([A-Z]+)\s+([^\s]+)\s+HTTP/\d+(?:\.\d+)?$#', trim($requestLine), $matches)) {
                    fwrite($connection, "HTTP/1.1 400 Bad Request\r\nConnection: close\r\nContent-Length: 0\r\n\r\n");
                    fclose($connection);
                    continue;
                }

                $method = $matches[1];
                $requestTarget = $matches[2];

                if ($method !== 'GET') {
                    fwrite($connection, "HTTP/1.1 405 Method Not Allowed\r\nAllow: GET\r\nConnection: close\r\nContent-Length: 0\r\n\r\n");
                    fclose($connection);
                    continue;
                }

                $requestPath = parse_url($requestTarget, PHP_URL_PATH) ?: '/';

                if ($requestPath !== '/' . $hash) {
                    fwrite($connection, "HTTP/1.1 404 Not Found\r\nConnection: close\r\nContent-Length: 0\r\n\r\n");
                    fclose($connection);
                    continue;
                }

                $size = filesize($file->path);
                $mime = $file->mime() ?: 'application/octet-stream';
                $dispositionName = addcslashes($file->basename(), "\\\"");
                $handle = fopen($file->path, 'rb');

                if ($handle === false) {
                    fclose($connection);
                    throw FileException::unableToRead($file->path);
                }

                try {
                    fwrite($connection, "HTTP/1.1 200 OK\r\n");
                    fwrite($connection, "Content-Type: {$mime}\r\n");
                    fwrite($connection, 'Content-Length: ' . ($size === false ? 0 : $size) . "\r\n");
                    fwrite($connection, 'Content-Disposition: attachment; filename="' . $dispositionName . '"' . "\r\n");
                    fwrite($connection, "Connection: close\r\n\r\n");

                    while (!feof($handle)) {
                        $chunk = fread($handle, 8192);

                        if ($chunk === false) {
                            break;
                        }

                        fwrite($connection, $chunk);
                    }
                } finally {
                    fclose($handle);
                    fclose($connection);
                }

                return;
            }
        } finally {
            fclose($server);
        }
    }
}
