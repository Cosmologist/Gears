# BrowserControl

**Date**: 2026-08-02
**Status**: draft

## Problem

We need a PHP client to automate browser sessions by communicating with the `browser-controller` browser extension (Chrome/Firefox). The extension acts as a WebSocket client that connects to a local server to receive commands. Currently, there is a synchronous Python implementation, but we need an asynchronous PHP implementation.

## Goal

Create a `Cosmologist\Gears\Browser\BrowserControl` component. It will act as a WebSocket server using `cboden/ratchet`, accept the incoming connection from the browser extension, and expose an asynchronous (ReactPHP Promises) API to send commands (`navigate`, `html`, `execute`, `select`) and receive their responses.

## Scope

**In scope:**
- Asynchronous WebSocket server integration using `cboden/ratchet`.
- Internal queuing mechanism to handle the strict "one in-flight request at a time" constraint of the extension protocol.
- Mapping commands to Promises.
- Re-implementing the `userScrollDown` logic using asynchronous `execute` calls (equivalent to Python's `user_scroll_down`).

**Out of scope:**
- WSS (TLS) support (v1 of extension is `ws://` only).
- Synchronous blocking API (the API will be strictly asynchronous).
- Multi-browser orchestration (designed to control a single connected active extension).

## Interface

The primary user-facing interface will be exposed via ReactPHP Promises.

```php
namespace Cosmologist\Gears\Browser;

use React\Promise\PromiseInterface;

class BrowserControl 
{
    // ... server setup and connection handling ...

    public function navigate(string $url): PromiseInterface;
    public function html(): PromiseInterface;
    public function execute(string $code): PromiseInterface;
    public function select(string $selector): PromiseInterface;
    
    // Higher-level asynchronous method
    public function userScrollDown(int $stepPx = 800, int $stepDelayMs = 300, int $settleMs = 1500, int $stableRounds = 3): PromiseInterface;
}
```

## Constraints

- Requires ReactPHP EventLoop to run.
- The extension WebSocket protocol does not use message IDs; responses are matched to requests chronologically. Because the extension drops/rejects concurrent commands with `busy`, the PHP client MUST queue outgoing commands and only send the next command when the previous response is received.

## Open questions

- How exactly will the `BrowserControl` instance be constructed and started alongside an existing or new ReactPHP EventLoop in the user's application?
