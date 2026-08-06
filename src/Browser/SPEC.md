# BrowserControl API Client

## Problem

Provides a convenient programmatic interface for controlling the browser via the [`browser-controller`](https://github.com/Cosmologist/automato/tree/master/tools/browser-controller) extension from the [automato](https://github.com/Cosmologist/automato) project.
The extension works by opening a websocket connection to the application, which acts as a server.

## Goal

`Cosmologist/Gears/BrowserControl` is an infrastructural class that hides the lower-level implementation details from the calling code, offering a clean and understandable interface.

## Example

```php
$browser = new BrowserControl();
$browser->listen(180);
$browser->navigate('http://example.com');
$browser->getHtml();
// other browser_controller methods
...
$browser->disconnect();
```

## Implementation

For simplicity and lightweightness, the class uses the minimalistic synchronous websocket server `Cosmologist\Gears\Websocket\WebsocketServerSynchronous`.
## Tests

Tests cover:
- establishing a connection
- a simple call of the public methods of the extension and receiving a response for each
- closing the connection