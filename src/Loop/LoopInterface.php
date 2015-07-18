<?php
namespace Icicle\Loop;

use Icicle\Loop\Events\{ImmediateInterface, SignalInterface, SocketEventInterface, TimerInterface};

interface LoopInterface
{
    /**
     * Determines if the necessary components for the loop class are available.
     *
     * @return bool
     */
    public static function enabled(): bool;
    
    /**
     * Executes a single tick, processing callbacks and handling any available I/O.
     *
     * @param bool $blocking Determines if the tick should block and wait for I/O if no other tasks are scheduled.
     */
    public function tick(bool $blocking = true);
    
    /**
     * Starts the event loop.
     *
     * @return bool True if the loop was stopped, false if the loop exited because no events remained.
     *
     * @throws \Icicle\Loop\Exception\RunningError If the loop was already running.
     */
    public function run(): bool;
    
    /**
     * Stops the event loop.
     */
    public function stop();
    
    /**
     * Determines if the event loop is running.
     *
     * @return bool
     */
    public function isRunning(): bool;
    
    /**
     * Removes all events (I/O, timers, callbacks, signal handlers, etc.) from the loop.
     */
    public function clear();

    /**
     * Determines if there are any pending events in the loop. Returns true if there are no pending events.
     *
     * @return bool
     */
    public function isEmpty(): bool;
    
    /**
     * Performs any reinitializing necessary after forking.
     */
    public function reInit();
    
    /**
     * Sets the maximum number of callbacks set with LoopInterface::queue() that will be executed per tick.
     *
     * @param int $depth Maximum number of functions to execute each tick. Use 0 for unlimited.
     *
     * @return int Previous max depth.
     */
    public function maxQueueDepth(int $depth): int;
    
    /**
     * Queue a callback function to be run after all I/O has been handled in the current tick.
     * Callbacks are called in the order queued.
     *
     * @param callable $callback
     * @param mixed[]|null $args Array of arguments to be passed to the callback function.
     */
    public function queue(callable $callback, array $args = null);
    
    /**
     * Creates an event object that can be used to listen for available data on the stream socket.
     *
     * @param resource $resource
     * @param callable $callback
     *
     * @return \Icicle\Loop\Events\SocketEventInterface
     *
     * @throws \Icicle\Loop\Exception\ResourceBusyError If a poll was already created for the resource.
     */
    public function poll($resource, callable $callback): SocketEventInterface;
    
    /**
     * Creates an event object that can be used to wait for the socket resource to be available for writing.
     *
     * @param resource $resource
     * @param callable $callback
     *
     * @return \Icicle\Loop\Events\SocketEventInterface
     *
     * @throws \Icicle\Loop\Exception\ResourceBusyError If an await was already created for the resource.
     */
    public function await($resource, callable $callback): SocketEventInterface;
    
    /**
     * Creates a timer object connected to the loop.
     *
     * @param int|float $interval
     * @param bool $periodic
     * @param callable $callback
     * @param mixed[]|null $args
     *
     * @return \Icicle\Loop\Events\TimerInterface
     */
    public function timer(float $interval, bool $periodic, callable $callback, array $args = null): TimerInterface;
    
    /**
     * Creates an immediate object connected to the loop.
     *
     * @param callable $callback
     * @param mixed[]|null $args
     *
     * @return \Icicle\Loop\Events\ImmediateInterface
     */
    public function immediate(callable $callback, array $args = null): ImmediateInterface;

    /**
     * @param int $signo
     * @param callable $callback
     *
     * @return \Icicle\Loop\Events\SignalInterface
     */
    public function signal(int $signo, callable $callback): SignalInterface;

    /**
     * Determines if signal handling is enabled.
     * *
     * @return bool
     */
    public function signalHandlingEnabled(): bool;
}
