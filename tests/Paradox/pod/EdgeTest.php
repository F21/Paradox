<?php
namespace Paradox\pod;
use tests\Base;

/**
 * Tests for the edge pod.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class EdgeTest extends Base
{
    /**
     * Stores an instance of the edge pod.
     * @var Edge
     */
    protected $edge;

    /**
     * The graph name for this test case.
     * @var string
     */
    protected $graphName = 'CollectionManagerTestGraph';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $client = $this->getClient();

        //Try to delete any leftovers
        try {
            $client->deleteGraph($this->graphName);
        } catch (\Exception $e) {
            //Ignore any errors
        }

        $client->createGraph($this->graphName);

        $this->edge= new Edge($client->getToolbox());
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $client = $this->getClient();

        try {
            $client->deleteGraph($this->graphName);
        } catch (\Exception $e) {
            //Ignore any errors
        }
    }

    /**
     * Convinence function to get a client which uses a graph by default.
     * @param  string          $endpoint The address of the server.
     * @param  string          $username The username.
     * @param  string          $password The password.
     * @param  string          $graph    The optional name of the graph to manage.
     * @param  string          $database The optional database to use.
     * @return \Paradox\Client
     */
    protected function getClient($endpoint = null, $username = null, $password = null, $graph = null, $database = null)
    {
        if (!$endpoint) {
            $endpoint = $this->getDefaultEndpoint();
        }

        if (!$username) {
            $username = $this->getDefaultUsername();
        }

        if (!$password) {
            $password = $this->getDefaultPassword();
        }

        if (!$graph) {
            $graph = $this->graphName;
        }

        return parent::getClient($endpoint, $username, $password, $graph);
    }

    /**
     * @covers Paradox\pod\Edge::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Edge');

        //Then we need to get the property we wish to test
        //and make it accessible
        $toolbox = $reflectionClass->getProperty('_toolbox');
        $toolbox->setAccessible(true);

        $type = $reflectionClass->getProperty('_type');
        $type->setAccessible(true);

        $data = $reflectionClass->getProperty('_data');
        $data->setAccessible(true);

        $new = $reflectionClass->getProperty('_new');
        $new->setAccessible(true);

        $document = new Edge($this->getClient()->getToolbox(), array('test' => 'test') , false, 'mycollection/1', 'mycollection/2');

        $this->assertInstanceOf('Paradox\Toolbox', $toolbox->getValue($document), 'Constructor did not store a Paradox\Toolbox.');
        $this->assertEquals("edge", $type->getValue($document), 'The type of the created document should be "edge"');
        $this->assertEquals('test', $data->getValue($document)['test'], "The data in the created document does not match");
        $this->assertFalse($new->getValue($document), "The new state of the document is not false");

        $this->assertEquals("mycollection/1", $data->getValue($document)['_from'], 'The from vertex\'s id does not match');
        $this->assertEquals("mycollection/2", $data->getValue($document)['_to'], 'The to vertex\'s id does not match');
    }

    /**
     * @covers Paradox\pod\Edge::setLabel
     */
    public function testSetLabel()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Edge');

        //Then we need to get the property we wish to test
        //and make it accessible
        $data = $reflectionClass->getProperty('_data');
        $data->setAccessible(true);

        $this->edge->setLabel('friends');

        $this->assertArrayHasKey('$label', $data->getValue(($this->edge)), 'The edge data should have a $label key');
        $this->assertEquals('friends', $data->getvalue($this->edge)['$label'], "The label retrieved does not match the one assigned to the edge");
    }

    /**
     * @covers Paradox\pod\Edge::getLabel
     */
    public function testGetLabel()
    {
        $this->edge->setLabel('friends');

        $this->assertEquals('friends', $this->edge->getLabel(), "The label retrieved does not match the one assigned to the edge");
    }

    /**
     * @covers Paradox\pod\Edge::setTo
     */
    public function testSetTo()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Edge');

        //Then we need to get the property we wish to test
        //and make it accessible
        $to = $reflectionClass->getProperty('_to');
        $to->setAccessible(true);

        $client = $this->getClient();
        $vertex = $client->dispense("vertex");

        $edge = new Edge($client->getToolbox());

        $edge->setTo($vertex);

        $this->assertEquals($vertex, $to->getValue($edge), "The to vertex does not match the one assigned to the edge");
    }

    /**
     * @covers Paradox\pod\Edge::getToKey
     */
    public function testGetToKey()
    {
        $client = $this->getClient();
        $vertex = $client->dispense("vertex");
        $client->store($vertex);

        $edge = new Edge($client->getToolbox());

        $edge->setTo($vertex);

        $this->assertEquals($vertex->getPod()->getKey(), $edge->getToKey(), "The key of the stored vertex does not match the one we attached to the edge");
    }

    /**
     * @covers Paradox\pod\Edge::getToKey
     */
    public function testGetToKeyWithoutModel()
    {
        $edge = new Edge($this->getClient()->getToolbox(), array() , false, null, 'mycollection/1');

        $this->assertEquals('1', $edge->getToKey(), "The key of the stored vertex does not match the one we attached to the edge");
    }

    /**
     * @covers Paradox\pod\Edge::getToKey
     */
    public function testGetToKeyWithNoToVertex()
    {
        $this->assertNull($this->edge->getToKey(), "The key should be null since there is no to vertex");
    }

    /**
     * @covers Paradox\pod\Edge::setFrom
     */
    public function testSetFrom()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Edge');

        //Then we need to get the property we wish to test
        //and make it accessible
        $from = $reflectionClass->getProperty('_from');
        $from->setAccessible(true);

        $client = $this->getClient();
        $vertex = $client->dispense("vertex");

        $edge = new Edge($client->getToolbox());

        $edge->setFrom($vertex);

        $this->assertEquals($vertex, $from->getValue($edge), "The from vertex does not match the one assigned to the edge");
    }

        /**
     * @covers Paradox\pod\Edge::getFromKey
     */
    public function testGetFromKey()
    {
        $client = $this->getClient();
        $vertex = $client->dispense("vertex");
        $client->store($vertex);

        $edge = new Edge($client->getToolbox());

        $edge->setFrom($vertex);

        $this->assertEquals($vertex->getPod()->getKey(), $edge->getFromKey(), "The key of the stored vertex does not match the one we attached to the edge");
    }

    /**
     * @covers Paradox\pod\Edge::getFromKey
     */
    public function testGetFromKeyWithoutModel()
    {
        $edge = new Edge($this->getClient()->getToolbox(), array() , false, 'mycollection/1');

        $this->assertEquals('1', $edge->getFromKey(), "The key of the stored vertex does not match the one we attached to the edge");
    }

    /**
     * @covers Paradox\pod\Edge::getFromKey
     */
    public function testGetFromKeyWithNoFromVertex()
    {
        $this->assertNull($this->edge->getFromKey(), "The key should be null since there is no from vertex");
    }

    /**
     * @covers Paradox\pod\Edge::getFrom
     */
    public function testGetFromWithExistingFromModel()
    {
        $client = $this->getClient();
        $vertex = $client->dispense("vertex");

        $edge = new Edge($client->getToolbox());
        $edge->setFrom($vertex);

        $this->assertEquals($vertex, $edge->getFrom(), "The vertex returned by getFrom() is not the one we set as the from vertex");
    }

    /**
     * @covers Paradox\pod\Edge::getFrom
     */
    public function testGetFromWithJustThePodId()
    {
        $client = $this->getClient();
        $vertex = $client->dispense("vertex");
        $client->store($vertex);

        $edge = new Edge($client->getToolbox(), array(), true, $vertex->getId());
        $from = $edge->getFrom();

        $this->assertEquals($vertex->getId(), $from->getId(), "The vertex returned by getFrom() is not the one we set as the from vertex");
    }

    /**
     * @covers Paradox\pod\Edge::getFrom
     */
    public function testGetFromWithoutFromVertex()
    {
        $this->assertNull($this->edge->getFrom(), "No vertex should be returned, because the edge has no from vertex");
    }

        /**
     * @covers Paradox\pod\Edge::getTo
     */
    public function testGetToWithExistingToModel()
    {
        $client = $this->getClient();
        $vertex = $client->dispense("vertex");

        $edge = new Edge($client->getToolbox());
        $edge->setTo($vertex);

        $this->assertEquals($vertex, $edge->getTo(), "The vertex returned by getTo() is not the one we set as the to vertex");
    }

    /**
     * @covers Paradox\pod\Edge::getTo
     */
    public function testGetToWithJustThePodId()
    {
        $client = $this->getClient();
        $vertex = $client->dispense("vertex");
        $client->store($vertex);

        $edge = new Edge($client->getToolbox(), array(), true, null, $vertex->getId());
        $to = $edge->getTo();

        $this->assertEquals($vertex->getId(), $to->getId(), "The vertex returned by getTo() is not the one we set as the to vertex");
    }

    /**
     * @covers Paradox\pod\Edge::getTo
     */
    public function testGetToWithoutToVertex()
    {
        $this->assertNull($this->edge->getTo(), "No vertex should be returned, because the edge has no to vertex");
    }

    /**
     * @covers Paradox\pod\Edge::setInternalTo
     */
    public function testSetInternalTo()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Edge');

        //Then we need to get the property we wish to test
        //and make it accessible
        $data = $reflectionClass->getProperty('_data');
        $data->setAccessible(true);

        $setInternalTo = $reflectionClass->getMethod('setInternalTo');
        $setInternalTo->setAccessible(true);

        $setInternalTo->invoke($this->edge, "mycollection/123456");

        $this->assertEquals("mycollection/123456", $data->getValue($this->edge)['_to'], "The to vertex's id does not match the one we setted");
    }

    /**
     * @covers Paradox\pod\Edge::setInternalFrom
     */
    public function testSetInternalFrom()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Edge');

        //Then we need to get the property we wish to test
        //and make it accessible
        $data = $reflectionClass->getProperty('_data');
        $data->setAccessible(true);

        $setInternalFrom = $reflectionClass->getMethod('setInternalFrom');
        $setInternalFrom->setAccessible(true);

        $setInternalFrom->invoke($this->edge, "mycollection/123456");

        $this->assertEquals("mycollection/123456", $data->getValue($this->edge)['_from'], "The from vertex's id does not match the one we setted");
    }

    /**
     * @covers Paradox\pod\Edge::getReservedFields
     */
    public function testGetReservedFields()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Edge');

        $getReservedFields = $reflectionClass->getMethod('getReservedFields');
        $getReservedFields->setAccessible(true);

        $result = $getReservedFields->invoke($this->edge);

        $this->assertCount(5, $result, "The list of reserved fields should only contain 5 items");

        foreach ($result as $field) {
            $this->assertContains($field, array('_id', '_key', '_rev', '_from', '_to'), "The field $field is not a valid reserved field");
        }
    }

    /**
     * @covers Paradox\pod\Edge::toDriverDocument
     */
    public function testToDriverDocument()
    {
        $this->edge->setId('mycollection/123456');
        $this->edge->setRevision('myrevision');
        $this->edge->set('mykey', 'myvalue');

        $converted = $this->edge->toDriverDocument();
        $this->assertInstanceOf('triagens\ArangoDb\Edge', $converted, 'The converted edge is not of type \triagens\ArangoDb\triagens\ArangoDb\Edge');
        $this->assertEquals('mycollection/123456', $converted->getInternalId(), "The converted edge's id does not match");
        $this->assertEquals('myrevision', $converted->getRevision(), "The converted edge's revision does not match");
        $this->assertEquals('myvalue', $converted->get('mykey'), "The converted edge's data does not match");
    }
}
