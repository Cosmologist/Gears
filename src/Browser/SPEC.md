# BrowserControl Synchronous API

**Date**: 2026-08-05
**Status**: implemented

## Problem

The original `BrowserControl` implementation exposed `PromiseInterface` for all commands because it uses ReactPHP under the hood. For typical consumers of this library, working with promises adds unnecessary complexity, making the code harder to read and use compared to simple synchronous PHP execution. Users found the internal entities (Deferred, Promises, embedded servers) redundant when compared to standard Ratchet documentation examples.

## Goal

Hide the asynchronous complexity (Promises) and the ReactPHP Event Loop inside the library, providing a completely synchronous (linear) public API for developers. 

## Scope

In scope:
- Refactoring public methods in `BrowserControl` to wait for responses synchronously instead of returning `PromiseInterface`.
- Utilizing `react/async` `await()` (Fibers) internally so the Event Loop is not blocked during waits.
- Keeping the server instantiation inside `BrowserControl::open()` to maintain the single-class convenience.

Out of scope:
- Splitting the class into `BrowserServerRunner` and `BrowserControlComponent` (we decided to keep it unified for simplicity, as discussed).
- Developing new browser commands.

## Interface

The developer-facing PHP interface becomes synchronous:

```php
$browser = new BrowserControl();
$browser->open(); // Waits internally via Fibers until connected
$result = $browser->navigate('http://example.com');
```

## Constraints

- Must run on PHP 8.1+ to support Fibers (which `react/async` relies on for synchronous-like `await()`).
- The internal WebSocket logic and connection deferrals must still function properly using ReactPHP without blocking the process globally (no sleep() or similar).

## Architecture Decisions

### Why not use `Ratchet\App->run()`?
The official Ratchet documentation often shows launching servers via `$app = new Ratchet\App(...); $app->run();`. However, the `run()` method invokes a blocking, infinite event loop. Since `BrowserControl` is intended to be used as an inline client library (e.g., inside a controller) and not as a standalone daemon, calling a blocking `run()` would prevent any subsequent PHP code (like `$browser->navigate(...)`) from ever executing. We embed `IoServer` and use Fibers to keep the event loop running in the background without permanently blocking the main execution flow.

### Why not fork the process (`pcntl_fork`)?
Forking the process to run the server in a child and the client code in the parent introduces massive complexity due to Inter-Process Communication (IPC). The parent process would have no direct access to the WebSocket memory space in the child process. Additionally, the `pcntl` extension is CLI-only and often disabled in web environments, which would severely limit the library's compatibility. Fibers solve this cleanly within a single process.

## Open questions

- None.
