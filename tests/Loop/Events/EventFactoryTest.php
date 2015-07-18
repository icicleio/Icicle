<?php
namespace Icicle\Tests\Loop\Events;

use Icicle\Loop\Events\{EventFactory, ImmediateInterface, SignalInterface, SocketEventInterface, TimerInterface};
use Icicle\Loop\Manager\{
    ImmediateManagerInterface,
    SignalManagerInterface,
    SocketManagerInterface,
    TimerManagerInterface
};
use Icicle\Tests\TestCase;

class EventFactoryTest extends TestCase
{
    /**
     * @var \Icicle\Loop\Events\EventFactory
     */
    protected $factory;
    
    public function setUp()
    {
        $this->factory = new EventFactory();
    }
    
    public function createSockets()
    {
        return stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    }
    
    public function testCreateSocketEvent()
    {
        list($socket) = $this->createSockets();
        
        $callback = $this->createCallback(1);
        $callback->method('__invoke')
                 ->with($this->identicalTo($socket), $this->identicalTo(false));

        $manager = $this->getMock(SocketManagerInterface::class);

        $event = $this->factory->socket($manager, $socket, $callback);
        
        $this->assertInstanceOf(SocketEventInterface::class, $event);
        
        $this->assertSame($socket, $event->getResource());

        $event->call(false);
    }
    
    public function testCreateTimer()
    {
        $timeout = 0.1;
        $periodic = true;

        $manager = $this->getMock(TimerManagerInterface::class);

        $timer = $this->factory->timer($manager, $timeout, $periodic, $this->createCallback(1));
        
        $this->assertInstanceOf(TimerInterface::class, $timer);
        
        $this->assertSame($timeout, $timer->getInterval());
        $this->assertSame($periodic, $timer->isPeriodic());
        
        $timer->call();
    }
    
    public function testCreateImmediate()
    {
        $manager = $this->getMock(ImmediateManagerInterface::class);

        $immediate = $this->factory->immediate($manager, $this->createCallback(1));
        
        $this->assertInstanceOf(ImmediateInterface::class, $immediate);
        
        $immediate->call();
    }

    public function testCreateSignal()
    {
        $signo = 1;

        $manager = $this->getMock(SignalManagerInterface::class);

        $signal = $this->factory->signal($manager, $signo, $this->createCallback(1));

        $this->assertInstanceOf(SignalInterface::class, $signal);

        $signal->call();
    }
}
