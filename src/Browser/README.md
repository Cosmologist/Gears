# BrowserControl

`Cosmologist/Gears/Browser/BrowserControl` allows you to control a running browser - open URLs, get page content, interact with DOM elements, execute javascript code in the page context, and so on.

## How it works

To control the browser, the _tools/browser-controller_ extension is used, which is installed and enabled in the browser.
The extension connects to the running `BrowserControl`, which (using the `WebsocketServerSynchronous` component) monitors the connection establishment, sends commands to the extension, receives the results of their execution, and passes them to the calling code.

## What problem does this solve?

Controlling a browser in the context of a user profile is problematic.
The standard way to control a browser is the ChromeDevTools Protocol (supported not only by Chrome, but also by Firefox and other Chromium-based browsers). However, Chrome has disabled the ability to use CDP for a browser launched under the main user's profile.
In some cases, this creates inconveniences - for example, for automation tools, when instead of the tool performing its main task, you spend a large amount of time on automatic authorization, bypassing anti-fraud systems, etc.
`BrowserControl` solves this problem by using extension-based control, and also, since the extension is part of the parent project _automato_, there is an opportunity to add unique tools to it and keep it simple and lightweight.

## Examples of usage

```php
use Cosmologist\Gears\Browser\BrowserControl;

$browser = new BrowserControl();

// 1. Start the server and wait for the extension to connect
$browser->listen(8080, 180);

// 2. Open a page
$browser->navigate('http://example.com');

// 3. Get the HTML of the page
$html = $browser->getHtml();

// 4. Find elements by selector
$elements = $browser->select('h1, h2');

// 5. Execute arbitrary JS code on the page
$titleLength = $browser->execute('return document.title.length;');

// 6. Close the connection
$browser->disconnect();
```
