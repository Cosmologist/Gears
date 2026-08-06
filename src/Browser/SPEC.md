# BrowserControl API Client

## Problem

Предоставляет удобный программный интерфейс для управления бразуером через расширение [`browser-controller`](https://github.com/Cosmologist/automato/tree/master/tools/browser-controller) проекта [automato](https://github.com/Cosmologist/automato).
Расширение работает открывая websocket-соединение с приложением, которое выступает в качестве сервера.

## Goal

`Cosmologist/Gears/BrowserControl` является инфраструктурным классом скрывающим более низкоуровненные детали реализации от вызывающего кода, предлагая чистый и понятный интерфейс.

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

Для простоты и легковесности, класс сделан синхронным и реализует работу с сокетами самостоятельно, без использования сторонних библиотек.
Класс реализует публичный интерфейс предлагаемый расширением полностью - смотри [tools/browser-controller#request-contract](https://github.com/Cosmologist/automato/tree/master/tools/browser-controller#request-contract).

## Tests

Пишем один простой тест, который устанавливает соединение и вызывает все публичные методы предоставляемые расширением, а затем разрывает соединение.Расширение имитируется python-скриптом ext-mock.py.