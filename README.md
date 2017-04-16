**Icicle is now deprecated in favor of [Amp v2.0](http://amphp.org).** This version is is currently under development, but close to release. The v2.0 branches are *amp_v2* in all packages except the [main Amp package](https://github.com/amphp/amp), loop package, and postgres package, where v2.0 is the *master* branch.

# Icicle

[![Join the chat at https://gitter.im/icicleio/Icicle](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/icicleio/Icicle?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

**Icicle is a PHP library for writing *asynchronous* code using *synchronous* coding techniques.**

Icicle uses [Coroutines](https://icicle.io/docs/manual/coroutines/) built with [Awaitables](https://icicle.io/docs/manual/awaitables/) and [Generators](http://www.php.net/manual/en/language.generators.overview.php) to facilitate writing asynchronous code using techniques normally used to write synchronous code, such as returning values and throwing exceptions, instead of using nested callbacks typically found in asynchronous code.

[![Build Status](https://img.shields.io/travis/icicleio/icicle/v1.x.svg?style=flat-square)](https://travis-ci.org/icicleio/icicle)
[![Coverage Status](https://img.shields.io/coveralls/icicleio/icicle/v1.x.svg?style=flat-square)](https://coveralls.io/r/icicleio/icicle)
[![Semantic Version](https://img.shields.io/github/release/icicleio/icicle.svg?style=flat-square)](http://semver.org)
[![MIT License](https://img.shields.io/packagist/l/icicleio/icicle.svg?style=flat-square)](LICENSE)
[![@icicleio on Twitter](https://img.shields.io/badge/twitter-%40icicleio-5189c7.svg?style=flat-square)](https://twitter.com/icicleio)

#### Library Components

- **[Coroutines](https://icicle.io/docs/api/Coroutine/)** are interruptible functions for building asynchronous code using synchronous coding patterns and error handling.
- **[Awaitables](https://icicle.io/docs/api/Awaitable/)** act as placeholders for future values of asynchronous operations. Awaitables can be yielded in coroutines to define interruption points. Callbacks registered with awaitables may return values and throw exceptions.
- **[Observables](https://icicle.io/docs/api/Observable/)** represent asynchronous sets of values, providing operations usually associated with sets such as map, filter, and reduce. Observables also can be iterated over asynchronously within a coroutine.
- **[Loop (event loop)](https://icicle.io/docs/api/Loop/)** is used to schedule functions, run timers, handle signals, and poll sockets for pending data or await for space to write.

#### Available Packages

- **[Stream](https://icicle.io/docs/api/Stream/)**: Common coroutine-based interface for reading and writing data.
- **[Socket](https://icicle.io/docs/api/Socket/)**: Asynchronous stream socket server and client.
- **[Concurrent](https://icicle.io/docs/api/Concurrent/)**: Provides an easy to use interface for parallel execution with non-blocking communication and task execution.
- **[DNS](https://icicle.io/docs/api/Dns/)**: Asynchronous DNS query executor, resolver and connector.
- **[Filesystem](https://github.com/icicleio/filesystem)**: Asynchronous filesystem access.
- **[HTTP](https://github.com/icicleio/http)**: Asynchronous HTTP server and client.
- **[WebSocket](https://github.com/icicleio/websocket)**: Asynchronous WebSocket server and client.
- **[React Adapter](https://github.com/icicleio/react-adapter)**: Adapts the event loop and awaitables of Icicle to interfaces compatible with components built for React.

#### Documentation and Support

- [Full API Documentation](https://icicle.io/docs/)
- [Official Twitter](https://twitter.com/icicleio)
- [Gitter Chat](https://gitter.im/icicleio/icicle)

##### Requirements

- PHP 5.5+ for v0.9.x branch (current stable) and v1.x branch (mirrors current stable)
- PHP 7 for v2.0 (master) branch supporting generator delegation and return expressions

##### Installation

The recommended way to install Icicle is with the [Composer](http://getcomposer.org/) package manager. (See the [Composer installation guide](https://getcomposer.org/doc/00-intro.md) for information on installing and using Composer.)

Run the following command to use Icicle in your project: 

```bash
composer require icicleio/icicle
```

You can also manually edit `composer.json` to add Icicle as a project requirement.

```js
// composer.json
{
    "require": {
        "icicleio/icicle": "^0.9"
    }
}
```

##### Suggested

- [pcntl extension](http://php.net/manual/en/book.pcntl.php): Enables custom signal handling.
- [ev extension](https://pecl.php.net/package/ev): Extension providing the most performant event loop implementation.
- [uv extension](https://github.com/bwoebi/php-uv) (PHP 7 only): Another extension providing a more performant event loop implementation (experimental).

#### Example

The example script below demonstrates how [awaitables](https://icicle.io/docs/manual/awaitables/) can be yielded in a [coroutine](https://icicle.io/docs/manual/coroutines/) to create interruption points. Fulfillment values of awaitables are sent to the coroutine and rejection exceptions are thrown into the coroutine.

```php
#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Awaitable;
use Icicle\Coroutine\Coroutine;
use Icicle\Loop;

$generator = function () {
    try {
        // Sets $start to the value returned by microtime() after approx. 1 second.
        $start = (yield Awaitable\resolve(microtime(true))->delay(1));

        echo "Sleep time: ", microtime(true) - $start, "\n";

        // Throws the exception from the rejected promise into the coroutine.
        yield Awaitable\reject(new Exception('Rejected promise'));
    } catch (Exception $e) { // Catches promise rejection reason.
        echo "Caught exception: ", $e->getMessage(), "\n";
    }

    yield Awaitable\resolve('Coroutine completed');
};

$coroutine = new Coroutine($generator());

$coroutine->done(function ($data) {
    echo $data, "\n";
});

Loop\run();
```
