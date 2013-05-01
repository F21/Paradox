<?php
namespace tests\Paradox;
use tests\Base;
use Paradox\Event;

/**
 * Tests for the event object.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class EventTest extends Base
{
    /**
     * @var Event
     */
    protected $event;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $object = new \stdClass();
        $object->name = "testObject";

        $this->event = new Event("testEvent", $object);
    }

    /**
     * @covers Paradox\Event::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\Event');

        //Then we need to get the property we wish to test
        //and make it accessible
        $eventName = $reflectionClass->getProperty('_eventName');
        $eventName->setAccessible(true);

        $object = $reflectionClass->getProperty('_object');
        $object->setAccessible(true);

        $this->assertInternalType('string', $eventName->getValue($this->event), 'The event name should be a string');
        $this->assertEquals('testEvent', $eventName->getValue($this->event), 'The event name should be "testEvent"');

        $this->assertInstanceOf('stdClass', $object->getValue($this->event), 'The object should be an instance of stdClass');
        $this->assertEquals('testObject', $object->getValue($this->event)->name, 'The object\'s name should be "testObject"');
    }

    /**
     * @covers Paradox\Event::getEvent
     */
    public function testGetEvent()
    {
        $this->assertEquals("testEvent", $this->event->getEvent(), 'The event type should be "testevent"');
    }

    /**
     * @covers Paradox\Event::getObject
     */
    public function testGetObject()
    {
        $object = $this->event->getObject();

        $this->assertInstanceOf('\stdClass', $object, "The object should be an instance of stdClass");
        $this->assertEquals("testObject", $object->name, 'The object\'s name should be "testObject"');
    }

    /**
     * @covers Paradox\Event::stopPropagation
     */
    public function testStopPropagation()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\Event');

        //Then we need to get the property we wish to test
        //and make it accessible
        $propagationStopped = $reflectionClass->getProperty('_propagationStopped');
        $propagationStopped->setAccessible(true);

        $this->assertFalse($propagationStopped->getValue($this->event), "The propagation should not be stopped");

        $this->event->stopPropagation();

        $this->assertTrue($propagationStopped->getValue($this->event), "The propagation should be stopped");
    }

    /**
     * @covers Paradox\Event::propagationStopped
     */
    public function testPropagationStopped()
    {
        $this->assertFalse($this->event->propagationStopped(), "The propagation should not be stopped");

        $this->event->stopPropagation();

        $this->assertTrue($this->event->propagationStopped(), "The propagation should be stopped");
    }
}
