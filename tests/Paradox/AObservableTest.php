<?php
namespace tests\Paradox;
use tests\Base;

/**
 * Tests for the observable abstract class.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class AObservableTest extends Base
{
    /**
     * @var AObservable
     */
    protected $observable;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->observable = $this->getMockForAbstractClass('Paradox\AObservable');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    protected function getObserver()
    {
        $observer = $this->getMock('Paradox\IObserver');

        $observer->expects($this->any())
        ->method('onEvent')
        ->will($this->returnArgument(1));

        return $observer;
    }

    /**
     * @covers Paradox\AObservable::attach
     */
    public function testAttach()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\AObservable');

        //Then we need to get the property we wish to test
        //and make it accessible
        $observers = $reflectionClass->getProperty('_observers');
        $observers->setAccessible(true);

        $this->observable->attach('testevent', $this->getObserver());
        $attached = $observers->getValue($this->observable);

        $this->assertInternalType('array', $attached, "The list of attached listeners should be an array");
        $this->assertCount(1, $attached, "The attached array should only have 1 element");
        $this->assertArrayHasKey('testevent', $attached, 'The attached event should be "testevent"');

        $this->assertCount(1, $attached["testevent"], "Only 1 listener should be attached to the event");

        //Attach a second listener
        $this->observable->attach('testevent', $this->getObserver());
        $attached = $observers->getValue($this->observable);
        $this->assertCount(2, $attached["testevent"], "Only 2 listeners should be attached to the event");

        foreach ($attached['testevent'] as $listener) {
            $this->assertInstanceOf('Paradox\IObserver', $listener, "Listeners should implement Paradox\IObservable");
        }

    }

    /**
     * @covers Paradox\AObservable::attach
     */
    public function testAttachOnMultipleEvents()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\AObservable');

        //Then we need to get the property we wish to test
        //and make it accessible
        $observers = $reflectionClass->getProperty('_observers');
        $observers->setAccessible(true);

        $this->observable->attach(array('testevent', 'testevent2'), $this->getObserver());
        $attached = $observers->getValue($this->observable);

        $this->assertInternalType('array', $attached, "The list of attached listeners should be an array");
        $this->assertCount(2, $attached, "The attached array should only have 2 elements");
        $this->assertArrayHasKey('testevent', $attached, 'The attached event should be "testevent"');
        $this->assertArrayHasKey('testevent', $attached, 'The attached event should be "testevent2"');

        $this->assertCount(1, $attached["testevent"], "Only 1 listener should be attached to the event");
        $this->assertCount(1, $attached["testevent2"], "Only 1 listener should be attached to the event");

        foreach ($attached as $event => $listeners) {

            foreach ($listeners as $listener) {
                $this->assertInstanceOf('Paradox\IObserver', $listener, "Listeners should implement Paradox\IObservable");
            }
        }
    }

    /**
     * @covers Paradox\AObservable::attach
     */
    public function testAttachWithInvalidEvent()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\AObservable');

        //Then we need to get the property we wish to test
        //and make it accessible
        $observers = $reflectionClass->getProperty('_observers');
        $observers->setAccessible(true);

        try {
            $this->observable->attach(new \stdClass(), $this->getObserver());
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ObservableException', $e, 'Exception thrown was not a Paradox\exceptions\ObservableException');

            return;
        }

        $this->fail('Passing an invalid argument to attach() did not throw an exception');
    }

    /**
     * @covers Paradox\AObservable::doAttach
     */
    public function testDoAttach()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\AObservable');

        //Then we need to get the property we wish to test
        //and make it accessible
        $observers = $reflectionClass->getProperty('_observers');
        $observers->setAccessible(true);

        $doAttach = $reflectionClass->getMethod('doAttach');
        $doAttach->setAccessible(true);

        $attached = $observers->getValue($this->observable);
        $this->assertEmpty($attached, "There should be no listeners attached");

        $observer = $this->getObserver();

        $doAttach->invoke($this->observable, 'testEvent', $observer);

        $attached = $observers->getValue($this->observable);
        $this->assertCount(1, $attached, "There should be 1 event");
        $this->assertCount(1, $attached['testEvent'], 'There should be 1 listener.');

        $doAttach->invoke($this->observable, 'testEvent', $observer);

        $attached = $observers->getValue($this->observable);
        $this->assertCount(1, $attached, "There should be 1 event");
        $this->assertCount(1, $attached['testEvent'], 'There should be 1 listener.');
    }

    /**
     * @covers Paradox\AObservable::detach
     */
    public function testDetachEvent()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\AObservable');

        //Then we need to get the property we wish to test
        //and make it accessible
        $observers = $reflectionClass->getProperty('_observers');
        $observers->setAccessible(true);

        //Detach an event that does not exist
        $attached = $observers->getValue($this->observable);
        $this->assertEmpty($attached, "There should be no listeners attached");

        $this->observable->detach('someEvent', $this->getObserver());

        $attached = $observers->getValue($this->observable);
        $this->assertEmpty($attached, "There should be no listeners attached");

        //Attach an observer
        $observer = $this->getObserver();
        $this->observable->attach('testEvent', $observer);

        $attached = $observers->getValue($this->observable);

        $this->assertNotEmpty($attached, "The listeners array should not be empty");
        $this->assertNotEmpty($attached['testEvent'], 'There should be listeners attached to the "testEvent"');

        //Detach it and confirm
        $this->observable->detach('testEvent', $observer);

        $attached = $observers->getValue($this->observable);

        $this->assertEmpty($attached['testEvent'], 'There should be no listeners attached for the "testEvent" event');
    }

    /**
     * @covers Paradox\AObservable::detachAllObserversForEvent
     */
    public function testDetachAllObserversForEvent()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\AObservable');

        //Then we need to get the property we wish to test
        //and make it accessible
        $observers = $reflectionClass->getProperty('_observers');
        $observers->setAccessible(true);

        //Attach multiple listeners
        $this->observable->attach('testEvent', $this->getObserver());
        $this->observable->attach('testEvent', $this->getObserver());

        //Confirm that it worked
        $attached = $observers->getValue($this->observable);
        $this->assertNotEmpty($attached, "There should be listeners attached");
        $this->assertCount(2, $attached['testEvent'], 'There should be 2 listeners for the "testEvent" event"');

        $this->observable->detachAllObserversForEvent('testEvent');

        //Confirm there are no more listeners for testEvent
        $attached = $observers->getValue($this->observable);
        $this->assertArrayNotHasKey('testEvent', $attached, 'There should be no listeners for the "testEvent" event"');
    }

    /**
     * @covers Paradox\AObservable::detachAllObserversForEvent
     */
    public function testDetachAllObserversForNonExistentEvent()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\AObservable');

        //Then we need to get the property we wish to test
        //and make it accessible
        $observers = $reflectionClass->getProperty('_observers');
        $observers->setAccessible(true);

        //Confirm that the event is not attached
        $attached = $observers->getValue($this->observable);
        $this->assertEmpty($attached, "There should be no listeners attached");
        $this->assertArrayNotHasKey('testEvent', $attached, 'The event "testEvent" is not attached');

        $this->observable->detachAllObserversForEvent('testEvent');

        //Confirm there are no more listener for testEvent
        $this->assertEmpty($attached, "There should be no listeners attached");
    }

    /**
     * @covers Paradox\AObservable::detachAllEventsForObserver
     */
    public function testDetachAllEventsForObserver()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\AObservable');

        //Then we need to get the property we wish to test
        //and make it accessible
        $observers = $reflectionClass->getProperty('_observers');
        $observers->setAccessible(true);

        //Attach
        $observer = $this->getObserver();
        $this->observable->attach('testEvent1', $observer);
        $this->observable->attach('testEvent2', $observer);

        //Confirm the attaching worked
        $attached = $observers->getValue($this->observable);

        $this->assertArrayHasKey('testEvent1', $attached, 'The "testEvent1" event was not attached');
        $this->assertArrayHasKey('testEvent2', $attached, 'The "testEvent2" event was not attached');
        $this->assertCount(2, $attached, "There should only be 2 attached events");

        foreach ($attached as $event => $listeners) {
            $this->assertCount(1, $listeners, "There should only be 1 listener for the event");

            foreach ($listeners as $listener) {
                $this->assertEquals($observer, $listener, "The attached listener does not match the one we attached");
            }
        }

        //Detach
        $this->observable->detachAllEventsForObserver($observer);
        $attached = $observers->getValue($this->observable);

        foreach ($attached as $event => $listeners) {
            $this->assertEmpty($listeners, "There should be no listeners for this event");
        }

    }

    /**
     * @covers Paradox\AObservable::notify
     */
    public function testNotify()
    {
        //Observer
        $observer = $this->getMock('Paradox\IObserver');

        $observer->expects($this->once())
        ->method('onEvent')
        ->with($this->isInstanceOf('Paradox\Event'));

        //Attach
        $this->observable->attach('testEvent', $observer);

        //Fire the event
        $this->observable->notify('testEvent', new \stdClass());
    }

    /**
     * @covers Paradox\AObservable::notify
     */
    public function testNotifyWithStopPropagation()
    {
        //Observer
        $observer = $this->getMock('Paradox\IObserver');

        $observer->expects($this->once())
        ->method('onEvent')
        ->will($this->returnCallback(function ($eventObject) {
            $eventObject->stopPropagation();
        }));

        $observer2 = $this->getMock('Paradox\IObserver');

        $observer2->expects($this->never())
        ->method('onEvent');

        //Attach
        $this->observable->attach('testEvent', $observer);
        $this->observable->attach('testEvent', $observer2);

        //Fire the event
        $this->observable->notify('testEvent', new \stdClass());
    }
}
