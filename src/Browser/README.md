# BrowserControl

`BrowserControl` is a PHP component within the `Cosmologist/Gears` library that provides a programmatic API to control browser sessions via the `browser-controller` extension.

## What problem does this solve?

It allows PHP applications to automate browser tasks (like navigation, executing JavaScript, and querying the DOM) natively. Instead of relying on heavy external drivers like Selenium or Puppeteer, it communicates directly with a lightweight browser extension over WebSockets.

## How to run it

This component acts as a WebSocket server and requires a ReactPHP event loop. You initialize the `BrowserControl` server on a specific port, wait for the browser extension to connect, and then issue asynchronous commands.

## Examples of usage

```php
use Cosmologist\Gears\Browser\BrowserControl;
use React\EventLoop\Loop;

$browser = new BrowserControl();

// Start the server on port 42777 and wait for the extension to connect
$browser->connect(42777)->then(function () use ($browser) {
    echo "Extension connected!\n";
    
    // Navigate to a URL
    return $browser->navigate('https://example.com');
})->then(function () use ($browser) {
    // Get the HTML content
    return $browser->html();
})->then(function ($response) {
    echo "HTML Length: " . strlen($response['html']) . "\n";
})->catch(function ($error) {
    echo "Error: " . $error->getMessage() . "\n";
});

// Run the event loop
Loop::run();
```
