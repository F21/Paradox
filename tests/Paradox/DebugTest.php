<?php
namespace tests\Paradox;
use tests\Base;
use Paradox\Debug;

/**
 * Tests for the debugger.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class DebugTest extends Base
{
    /**
     * Holds the debugger.
     * @var Debug
     */
    protected $debug;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->debug = new Debug();
    }

    /**
     * @covers Paradox\Debug::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\Debug');

        //Then we need to get the property we wish to test
        //and make it accessible
        $debug = $reflectionClass->getProperty('_debug');
        $debug->setAccessible(true);

        $debugger = new Debug(true);

        $this->assertTrue($debug->getValue($debugger), "The debug value should be true");

        $debugger = new Debug(false);

        $this->assertFalse($debug->getValue($debugger), "The debug value should be false");
    }

    /**
     * @covers Paradox\Debug::__invoke
     * @covers Paradox\Debug::trace
     */
    public function test__invokeWithRequest()
    {
        $request = new \triagens\ArangoDb\TraceRequest(array("Some-Header" => "somevalue"), "GET", "/_api/some/endpoint", '{"key": "value"}');

        $this->setOutputCallback(function($output){
            $this->assertInternalType('string', $output);
        });

        $this->debug->setDebug(true);
        $debugger = $this->debug;
        $debugger($request);
    }

    /**
     * @covers Paradox\Debug::__invoke
     * @covers Paradox\Debug::trace
     */
    public function test__invokeWithResponse()
    {
        $request = new \triagens\ArangoDb\TraceResponse(array("Some-Header" => "somevalue"), 200, '{"key": "value"}', 100);

        $this->setOutputCallback(function($output){
            $this->assertInternalType('string', $output);
        });

        $this->debug->setDebug(true);
        $debugger = $this->debug;
        $debugger($request);
    }

    /**
     * @covers Paradox\Debug::__invoke
     * @covers Paradox\Debug::trace
     */
    public function test__invokeWithResponseWithError()
    {
        $request = new \triagens\ArangoDb\TraceResponse(array("Some-Header" => "somevalue"), 400, '{"key": "value"}', 100);

        $this->setOutputCallback(function($output){
            $this->assertInternalType('string', $output);
        });

            $this->debug->setDebug(true);
            $debugger = $this->debug;
            $debugger($request);
    }

    /**
     * @covers Paradox\Debug::setDebug
     */
    public function testSetDebug()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\Debug');

        //Then we need to get the property we wish to test
        //and make it accessible
        $debug = $reflectionClass->getProperty('_debug');
        $debug->setAccessible(true);

        $this->debug->setDebug(true);

        $this->assertTrue($debug->getValue($this->debug), "The debug value should be true");

        $this->debug->setDebug(false);

        $this->assertFalse($debug->getValue($this->debug), "The debug value should be false");
    }
}
