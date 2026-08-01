# BrowserControl

**Date**: 2026-08-02
**Status**: draft

## Problem

We need a PHP client to control browser sessions via the external `browser-controller` extension (which supports Chrome and Firefox). The Python implementation exists but we need a native PHP equivalent within `Cosmologist/Gears` to automate browsing and query the DOM.

## Goal

Create a `Cosmologist\Gears\Browser\BrowserControl` component that acts as a WebSocket server. It will accept an incoming connection from the `browser-controller` extension, send it control commands, and return the results.

## Scope

In scope:
- WebSocket server built on top of `cboden/ratchet`.
- Asynchronous API returning ReactPHP promises.
- Supported commands: `navigate`, `html`, `execute`, `select`.
- Fail-fast mechanism for concurrent commands (returning an error/rejecting the promise if a command is sent while another is already in-flight).

Out of scope:
- Automatic queueing of commands.
- Support for multiple extension connections at once (designed for a 1:1 session).

## Interface

Primary user-facing interface will be the `BrowserControl` class, exposing async methods:
- `connect(int $port, float $timeout = 90.0): PromiseInterface`
- `navigate(string $url): PromiseInterface`
- `html(): PromiseInterface`
- `execute(string $code): PromiseInterface`
- `select(string $selector): PromiseInterface`
- `close(): void`

## Constraints

- Inherits ReactPHP event loop architecture.
- The extension protocol uses one text frame per JSON object and allows strictly one in-flight request at a time.
- If an overlapping request occurs, it must immediately be rejected by the client-side logic.

## Open questions

- How should the user start the ReactPHP loop? (Assume they manage the loop externally, to be detailed in the implementation plan).
