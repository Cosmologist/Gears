<?php

namespace Cosmologist\Gears;

use Uri\Rfc3986\Uri;

final class Network
{
    /**
     * Check if the value is a valid IP address
     */
    public static function isIp(string $ip, bool $allowIpV4 = true, bool $allowIpV6 = true): bool
    {
        if (($allowIpV4 || $allowIpV6) === false) {
            throw new \InvalidArgumentException('At least one IP version must be allowed');
        }

        $flags = 0;

        if ($allowIpV4) {
            $flags |= FILTER_FLAG_IPV4;
        }

        if ($allowIpV6) {
            $flags |= FILTER_FLAG_IPV6;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false;
    }

    /**
     * Assert that the value is a valid IP address
     *
     * @throws NetworkException If the value is not a valid IP address
     */
    public static function assertIp(string $ip, bool $allowIpV4 = true, bool $allowIpV6 = true): void
    {
        if (!self::isIp($ip, $allowIpV4, $allowIpV6)) {
            throw NetworkException::invalidIp($ip, $allowIpV4, $allowIpV6);
        }
    }

    /**
     * Start a one-time HTTP server and serve the file once by secret URL
     *
     * The server starts on the given IP and port, generates a unique URL with a hash,
     * passes that URL to the callback, then waits until the matching HTTP request arrives.
     * After the file is fully sent, the server stops and the method returns.
     *
     * @param  callable(string):void  $urlRecipient  Receives generated URL, useful when port is auto-assigned
     * @param  int  $port  TCP port, `0` lets OS pick a free one
     *
     * @throws FileException If the target does not exist, is not a regular file, or cannot be read
     * @throws NetworkException If the server cannot be started
     */
    public static function serve(File $file, callable $urlRecipient, string $ip = '0.0.0.0', int $port = 0): void
    {
        $file->assertFile();
        self::assertIp($ip);

        $bindIp = self::isIp($ip, allowIpV4: false, allowIpV6: true) ? "[{$ip}]" : $ip;
        $address = "tcp://{$bindIp}:{$port}";
        $errorCode = 0;
        $errorMessage = '';
        $server = stream_socket_server($address, $errorCode, $errorMessage);

        if ($server === false) {
            throw NetworkException::unableToServe($address, $errorMessage, $errorCode);
        }

        try {
            $hash = bin2hex(random_bytes(16));
            preg_match('/:(\d+)$/', stream_socket_get_name($server, false), $portMatches);
            $serverPort = (int) ($portMatches[1] ?? $port);
            $uriHost = self::isIp($ip, allowIpV4: false, allowIpV6: true) ? "[{$ip}]" : $ip;

            $url = (new Uri("http://{$uriHost}:{$serverPort}"))
                ->withPath('/' . $hash)
                ->toString();

            $urlRecipient($url);

            while (true) {
                // -1 means wait indefinitely for the single allowed download request.
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
