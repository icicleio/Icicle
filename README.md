# Icicle

[![Join the chat at https://gitter.im/icicleio/Icicle](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/icicleio/Icicle?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

**Icicle is a PHP library for writing *asynchronous* code using *synchronous* coding techniques.**

Icicle uses [Coroutines](#coroutines) built with [Promises](#promises) to facilitate writing asynchronous code using techniques normally used to write synchronous code, such as returning values and throwing exceptions, instead of using nested callbacks typically found in asynchronous code.

[![@icicleio on Twitter](https://img.shields.io/badge/twitter-%40icicleio-5189c7.svg?style=flat-square)](https://twitter.com/icicleio)
[![Build Status](https://img.shields.io/travis/icicleio/Icicle/master.svg?style=flat-square)](https://travis-ci.org/icicleio/Icicle)
[![Coverage Status](https://img.shields.io/coveralls/icicleio/Icicle.svg?style=flat-square)](https://coveralls.io/r/icicleio/Icicle)
[![Apache 2 License](https://img.shields.io/packagist/l/icicleio/Icicle.svg?style=flat-square)](LICENSE)

#### Library Constructs

- [Coroutines](#coroutines): Interruptible functions for building asynchronous code using synchronous coding patterns and error handling.
- [Promises](#promises): Placeholders for future values of asynchronous operations. Callbacks registered with promises may return values and throw exceptions.
- [Loop (event loop)](#loop): Used to schedule functions, run timers, handle signals, and poll sockets for pending data or await for space to write.
- [Streams](#streams): Common interface for reading and writing data.
- [Sockets](#sockets): Implement asynchronous network and file operations.
- [Event Emitters](#event-emitters): Allows objects to emit events that execute a set of registered callbacks.

##### Requirements

- PHP 5.4+ (PHP 5.5+ required for [coroutines](#coroutines)).

##### Installation

The recommended way to install Icicle is with the [Composer](http://getcomposer.org/) package manager. (See the [Composer installation guide](https://getcomposer.org/doc/00-intro.md) for information on installing and using Composer.)

Use the following command to use Icicle in your project, then run 

```bash
composer require icicleio/icicle 0.1.*
```

You can also manually edit `composer.json` to add Icicle as a project requirement.

```js
// composer.json
{
    "require": {
        "icicleio/icicle": "0.1.*"
    }
}
```

##### Download

Icicle may also be [downloaded as a zip package](https://icicle.io/files/icicle-latest.zip). It is compatible with any [PSR-4](http://www.php-fig.org/psr/psr-4/) compliant autoloader when the `Icicle` namespace is loaded from the `src` directory.

##### Suggested

- [openssl extension](http://php.net/manual/en/book.openssl.php): Enables using SSL/TLS on sockets.
- [pcntl extension](http://php.net/manual/en/book.pcntl.php): Enables custom signal handling.
- [event extension](http://pecl.php.net/package/event): Allows for the most performant event loop implementation.
- [libevent extension](http://pecl.php.net/package/libevent): Similar to the event extension, it allows for a more performant event loop implementation.

## Promises

Icicle implements promises based on the [Promises/A+](http://promisesaplus.com) specification, adding support for cancellation.

Promises are objects that act as placeholders for the future value of an asynchronous operation. Pending promises may either be fulfilled with any value (including other promises, `null`, and exceptions) or rejected with any value (non-exceptions are encapsulated in an exception). Once a promise is fulfilled or rejected (resolved) with a value, the promise cannot becoming pending and the resolution value cannot change.

Callback functions are the primary way of accessing the resolution value of promises. Unlike other APIs that use callbacks, **promises provide an execution context to callback functions, allowing callbacks to return values and throw exceptions**.

All promise objects implement a common interface: `Icicle\Promise\PromiseInterface`. While the primary promise implementation is `Icicle\Promise\Promise`, several other classes also implement `Icicle\Promise\PromiseInterface`.

The `Icicle\Promise\PromiseInterface->then(callable $onFulfilled = null, callable $onRejected = null)` method is the primary way to register callbacks that receive either the value used to fulfill the promise or the exception used to reject the promise. A promise is returned by `then()`, which is resolved with the return value of a callback or rejected if a callback throws an exception.

The `Icicle\Promise\PromiseInterface->done(callable $onFulfilled = null, callable $onRejected = null)` method registers callbacks that should either consume promised values or handle errors. No value is returned from `done()`. Values returned by callbacks registered using `done()` are ignored and exceptions thrown from callbacks are re-thrown in an uncatchable way.

*[More on using callbacks to interact with promises...](//github.com/icicleio/Icicle/tree/master/src/Promise#interacting-with-promises)*

```php
use Icicle\Loop\Loop;

$promise1 = doAsynchronousTask(); // Function returning a promise.

$promise2 = $promise1->then(
    function ($value) { // Called if $promise1 is fulfilled.
        if (null === $value) {
            throw new Exception("Invalid value!"); // Rejects $promise2.
        }
		
		return anotherAsynchronousTask($value); // Another function returning a promise.
		// $promise2 will adopt the state of the promise returned above.
    }
);

$promise2->done(
    function ($value) { // Called if $promise2 is fulfilled.
        echo "Asynchronous task succeeded: {$value}\n";
    },
    function (Exception $exception) { // Called if $promise1 or $promise2 is rejected.
        echo "Asynchronous task failed: {$exception->getMessage()}\n";
    }
);

Loop::run();
```

In the above example, the functions `doAsynchronousTask()` and `anotherAsynchronousTask()` both return promises. `$promise1` created by `doAsynchronousTask()` will either be fulfilled or rejected:

- If `$promise1` is fulfilled, the callback function registered in the call to `$promise1->then()` is executed. If `$value` (the resolution value of `$promise1`) is `null`, `$promise2` is rejected with the exception thrown in the callback. Otherwise `$value` is used as a parameter to `anotherAsynchronousTask()`, which returns a new promise. The resolution of `$promise2` will then be determined by the resolution of this promise (`$promise2` will adopt the state of the promise returned by `anotherAsynchronousTask()`).
- If `$promise1` is rejected, `$promise2` is rejected since no `$onRejected` callback was registered in the call to `$promise1->then()`.

*[More on promise resolution and propagation...](//github.com/icicleio/Icicle/tree/master/src/Promise#resolution-and-propagation)*

##### Brief overview of promise API features

- Asynchronous resolution (callbacks are not called before `then()` or `done()` return).
- Convenience methods for registering special callbacks to handle promise resolution.
- Lazy execution of promise-creating functions.
- Operations on collections of promises to join, select, iterate, and map to other promises or values.
- Support for promise cancellation.
- Methods to convert synchronous functions or callback-based functions into functions accepting and returning promises.

**[Promise API documentation](//github.com/icicleio/Icicle/tree/master/src/Promise)**

## Coroutines

Coroutines are interruptible functions implemented using [Generators](http://www.php.net/manual/en/language.generators.overview.php). A `Generator` usually uses the `yield` keyword to yield a value from a set to implement an iterator. Coroutines use the `yield` keyword to define interruption points. When a coroutine yields a value, execution of the coroutine is temporarily interrupted, allowing other tasks to be run, such as I/O, timers, or other coroutines.

When a coroutine yields a [promise](#promises), execution of the coroutine is interrupted until the promise is resolved. If the promise is fulfilled with a value, the yield statement that yielded the promise will take on the resolved value. For example, `$value = (yield Icicle\Promise\Promise::resolve(2.718));` will set `$value` to `2.718` when execution of the coroutine is resumed. If the promise is rejected, the exception used to reject the promise will be thrown into the function at the yield statement. For example, `yield Icicle\Promise\Promise::reject(new Exception());` would behave identically to replacing the yield statement with `throw new Exception();`.

Note that **no callbacks need to be registered** with the promises yielded in a coroutine and **errors are reported using thrown exceptions**, which will bubble up to the calling context if uncaught in the same way exceptions bubble up in synchronous code.

The example below uses the `Icicle\Coroutine\Coroutine::call()` method to create a `Icicle\Coroutine\Coroutine` instance from a function returning a `Generator`.

```php
use Icicle\Coroutine\Coroutine;
use Icicle\Loop\Loop;

$coroutine = Coroutine::call(function () {
    try {
        $value = (yield doAsynchronousTask());
		
        if (null === $value) {
            throw new Exception("Invalid value!");
        }
		
		$value = (yield anotherAsynchronousTask($value));
		
        echo "Asynchronous task succeeded: {$value}\n";
    } catch (Exception $exception) {
        echo "Asynchronous task failed: {$exception->getMessage()}\n";
    }
});

Loop::run();
```

The example above does the same thing as the example section on [promises](#promises) above, but instead uses a coroutine to **structure asynchronous code like synchronous code** using a try/catch block, rather than creating and registering callback functions.

A `Icicle\Coroutine\Coroutine` object is also a [promise](#promises), implementing `Icicle\Promise\PromiseInterface`. The promise is fulfilled with the last value yielded from the generator (or fulfillment value of the last yielded promise) or rejected if an exception is thrown from the generator. A coroutine may then yield other coroutines, suspending execution until the yielded coroutine has resolved. If a coroutine yields a `Generator`, it will automatically be converted to a `Icicle\Coroutine\Coroutine` and handled in the same way as a yielded coroutine.

**[Coroutine API documentation](//github.com/icicleio/Icicle/tree/master/src/Coroutine)**

## Loop

The event loop schedules functions, runs timers, handles signals, and polls sockets for pending reads and available writes. There are several event loop implementations available depending on what PHP extensions are available. The `Icicle\Loop\SelectLoop` class uses only core PHP functions, so it will work on any PHP installation, but is not as performant as some of the other available implementations. All event loops implement `Icicle\Loop\LoopInterface` and provide the same features.

The event loop should be accessed via the static methods of the `Icicle\Loop\Loop` facade class. The `Icicle\Loop\Loop::init()` method allows a specific or custom implementation of `Icicle\Loop\LoopInterface` to be used as the event loop.

The `Icicle\Loop\Loop::run()` method runs the event loop and will not return until the event loop is stopped or no events are pending in the loop.

The following code demonstrates how functions may be scheduled to run later using the `Icicle\Loop\Loop::schedule()` method.

```php
use Icicle\Loop\Loop;

Loop::schedule(function () {
	echo "First.\n";
	Loop::schedule(function () {
	    echo "Second.\n";
	});
	echo "Third.\n";
	Loop::schedule(function () {
		echo "Fourth.\n";
	});
	echo "Fifth.\n";
});

echo "Starting event loop.\n";
Loop::run();
```

The above code will output:

```
Starting event loop.
First.
Third.
Fifth.
Second.
Fourth.
```

Scheduled functions will always be executed in the order scheduled. (Exact timing of the execution of scheduled functions varies and should not be relied upon.) `Icicle\Loop\Loop::schedule()` is used throughout Icicle to ensure callbacks are executed asynchronously.

**[Loop API documentation](//github.com/icicleio/Icicle/tree/master/src/Loop)**

## Streams

Streams represent a common promise-based API that may be implemented by classes that read or write sequences of binary data to facilitate interoperability. The stream component defines three interfaces, one of which should be used by all streams.

- `Icicle\Stream\ReadableStreamInterface`: Interface to be used by streams that are only readable.
- `Icicle\Stream\WritableStreamInterface`: Interface to be used by streams that are only writable.
- `Icicle\Stream\DuplexStreamInterface`: Interface to be used by streams that are readable and writable. Extends both `Icicle\Stream\ReadableStreamInterface` and `Icicle\Stream\WritableStreamInterface`.

**[Streams API documentation](//github.com/icicleio/Icicle/tree/master/src/Stream)**

## Sockets

The socket component implements network sockets as promise-based streams, server, and datagram. Creating a server and accepting connections is very simple, requiring only a few lines of code.

The example below implements HTTP server that responds to any request with `Hello world!` implemented using the promise-based server and client provided by the Socket component.

```php
use Icicle\Loop\Loop;
use Icicle\Socket\ClientInterface;
use Icicle\Socket\Server;

$server = Server::create('localhost', 60000);

$handler = function (ClientInterface $client) use (&$handler, &$error, $server) {
    $server->accept()->done($handler, $error);
    
    $client->write("HTTP/1.1 200 OK\r\n");
    $client->write("Content-Length: 12\r\n");
    $client->write("Connection: close\r\n");
    $client->write("\r\n");
    
    $client->end("Hello world!");
};

$error = function (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
};

$server->accept()->done($handler, $error);

echo "Server listening on {$server->getAddress()}:{$server->getPort()}\n";

Loop::run();

```

The example below shows the same HTTP server as above, instead implemented using a coroutine.

```php
use Icicle\Coroutine\Coroutine;
use Icicle\Loop\Loop;
use Icicle\Socket\ClientInterface;
use Icicle\Socket\Server;

$coroutine = Coroutine::call(function (Server $server) {
    echo "Server listening on {$server->getAddress()}:{$server->getPort()}\n";
    
    $handler = Coroutine::async(function (ClientInterface $client) {
        $client->write("HTTP/1.1 200 OK\r\n");
        $client->write("Content-Length: 12\r\n");
        $client->write("Connection: close\r\n");
        $client->write("\r\n");
        
        yield $client->end("Hello world!");
    });
    
    try {
        while ($server->isOpen()) {
            $handler(yield $server->accept());
        }
    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}, Server::create('localhost', 60000));

Loop::run();
```

**[Sockets API documentation](//github.com/icicleio/Icicle/tree/master/src/Socket)**

## Event Emitters

Event emitters can create a set of events identified by an integer or string to which other code can register callbacks that are invoked when the event occurs. Each event emitter should implement `Icicle\EventEmitter\EventEmitterInterface`, which can be done easily by using `Icicle\EventEmitter\EventEmitterTrait` in the class definition.

This implementation differs from other event emitter libraries by ensuring that *a callback can only be registered once on an event identifier*. An attempt to register a previously registered callback is a no-op.

Event identifiers are also strictly enforced to aid in debugging. *Event emitter objects must initialize event identifiers of events they wish to emit.* If an attempt to register a callback is made on a non-existent event, a `Icicle\EventEmitter\Exception\InvalidEventException` is thrown.

**[Event Emitter API documentation](//github.com/icicleio/Icicle/tree/master/src/EventEmitter)**

## Example

The example below shows how the components outlined above can be combined to quickly create an asynchronous echo server, capable of simultaneously handling many clients.

```php
use Icicle\Coroutine\Coroutine;
use Icicle\Loop\Loop;
use Icicle\Socket\ClientInterface;
use Icicle\Socket\Server;

// Connect using `nc localhost 60000`.

$coroutine = Coroutine::call(function (Server $server) {
    $handler = Coroutine::async(function (ClientInterface $client) {
        try {
            yield $client->write("Want to play shadow? (Type 'exit' to quit)\n");
			
            while ($client->isReadable()) {
                $data = (yield $client->read());
                
                $data = trim($data, "\n");
                
                if ("exit" === $data) {
                    yield $client->end("Goodbye!\n");
                } else {
                    yield $client->write("Echo: {$data}\n");
                }
            }
        } catch (Exception $e) {
            echo "Client error: {$e->getMessage()}\n";
            $client->close();
        }
    });
    
    echo "Echo server running on {$server->getAddress()}:{$server->getPort()}\n";
    
    while ($server->isOpen()) {
        try {
            $handler(yield $server->accept());
        } catch (Exception $e) {
            echo "Error accepting client: {$e->getMessage()}\n";
        }
    }
}, Server::create('localhost', 60000));

Loop::run();
```
