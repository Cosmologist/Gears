# BrowserControl

`BrowserControl` is a PHP component for controlling the [browser-controller](https://github.com/automato/browser-controller) browser extension via WebSockets. It acts as a WebSocket server that the extension connects to, providing an asynchronous, Promise-based API for browser automation.

## Problem Solved
Automating browser tasks usually requires heavy tools like Puppeteer or Selenium. `BrowserControl` allows lightweight browser automation by communicating directly with a minimal Manifest V3 browser extension (Chrome or Firefox) over a standard WebSocket connection.

## How it works
1. You start the `BrowserControl` WebSocket server in your PHP application.
2. The browser extension connects to this server.
3. You use the asynchronous API to send commands (`navigate`, `html`, `execute`, `select`) to the active tab in the browser.

## Example

```php
use Cosmologist\Gears\Browser\BrowserControl;
use React\EventLoop\Loop;

$control = new BrowserControl();
// Start server on ws://127.0.0.1:42777
$control->startServer(42777);

$control->on('connected', function () use ($control) {
    $control->navigate('https://example.com')
        ->then(function () use ($control) {
            return $control->html();
        })
        ->then(function ($response) {
            echo "HTML Length: " . strlen($response['html']) . "\n";
        });
});

Loop::run();
```
