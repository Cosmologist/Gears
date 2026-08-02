# BrowserControl Synchronous API

**Date**: 2026-08-02
**Status**: draft

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

## Open questions

- None.
