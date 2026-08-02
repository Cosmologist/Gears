# BrowserControl

`BrowserControl` is a PHP component within the `Cosmologist/Gears` library that provides a programmatic API to control browser sessions via the `browser-controller` extension.

## What problem does this solve?

It allows PHP applications to automate browser tasks (like navigation, executing JavaScript, and querying the DOM) natively. Instead of relying on heavy external drivers like Selenium or Puppeteer, it communicates directly with a lightweight browser extension over WebSockets.

## How it works

This component acts as a WebSocket server built on top of ReactPHP. However, all the asynchronous complexity (Promises, Deferreds, and Event Loops) is hidden under the hood. Using PHP 8.1 Fibers (via `react/async`), the API behaves synchronously for developers, making it as easy to use as blocking code, while keeping the network layer fully non-blocking.

## Examples of usage

```php
use Cosmologist\Gears\Browser\BrowserControl;

$browser = new BrowserControl();

// Starts the internal WebSocket server and waits until the browser extension connects.
$browser->open('127.0.0.1', 42777);

echo "Extension connected!\n";

// Commands are executed synchronously
$browser->navigate('https://example.com');
$html = $browser->html();

echo "HTML Length: " . strlen($html['html']) . "\n";

$browser->close();
```
