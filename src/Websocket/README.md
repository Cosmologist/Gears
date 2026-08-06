# Websocket Server

`Cosmologist/Gears/Websocket/WebsocketServerSynchronous` is an ultra-minimalistic websocket server operating in synchronous mode, written in pure PHP (`stream_*`) without requiring any third-party or heavy libraries.
The server implements the basic capabilities of the WebSocket protocol (RFC 6455) and is designed for a single-user connection.

## How it works

`WebsocketServerSynchronous` opens a TCP socket using `stream_socket_server` and blocks execution via `stream_select` until a client connects.
After establishing the connection, it performs a handshake and provides methods for synchronous sending and receiving of text frames.

## Examples of usage

```php
use Cosmologist\Gears\Websocket\WebsocketServerSynchronous;

$server = new WebsocketServerSynchronous();

// 1. Start the server and wait for a connection (blocking call)
$server->listen(8080, 180);

// 2. Send a message
$server->send('Hello WebSocket!');

// 3. Read a message from the client (blocking call)
$response = $server->receive();
echo "Client said: " . $response . "\n";

// 4. Close the connection
$server->disconnect();
```
