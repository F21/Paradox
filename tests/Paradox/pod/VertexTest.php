<?php
namespace tests\Paradox\pod;
use tests\Base;
use Paradox\pod\Vertex;
use Paradox\pod\Edge;

/**
 * Tests for the vertex pod.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class VertexTest extends Base
{
    /**
     * Stores an instance of the vertex pod.
     * @var Vertex
     */
    protected $vertex;

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

        $this->vertex= new Vertex($client->getToolbox());
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
     * @covers Paradox\pod\Vertex::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Vertex');

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

        $document = new Vertex($this->getClient()->getToolbox(), array('test' => 'test') , false);

        $this->assertInstanceOf('Paradox\Toolbox', $toolbox->getValue($document), 'Constructor did not store a Paradox\Toolbox.');
        $this->assertEquals("vertex", $type->getValue($document), 'The type of the created document should be "vertex"');
        $this->assertEquals('test', $data->getValue($document)['test'], "The data in the created document does not match");
        $this->assertFalse($new->getValue($document), "The new state of the document is not false");
    }

    /**
     * @covers Paradox\pod\Vertex::relateTo
     */
    public function testRelateTo()
    {
        $client = $this->getClient();

        $vertex1 = $client->dispense("vertex");
        $vertex2 = $client->dispense("vertex");

        $edge = $vertex1->relateTo($vertex2, "friends");

        $this->assertInstanceOf('Paradox\AModel', $edge, 'The created edge should be of the type Paradox\AModel');
        $this->assertInstanceOf('Paradox\pod\Edge', $edge->getPod(), 'The inner pod should be of the type Paradox\pod\Edge');
        $this->assertEquals($vertex1, $edge->getFrom(), "The to vertex should point to vertex1");
        $this->assertEquals($vertex2, $edge->getTo(), "The to vertex should point to vertex2");
        $this->assertEquals("friends", $edge->getLabel(), "The label does not match the one used to create the edge");
    }

    /**
     * @covers Paradox\pod\Vertex::getInboundEdges
     */
    public function testGetInboundEdges()
    {
        //Setup
        $client = $this->getClient();

        $horacio = $client->dispense("vertex");
        $horacio->set('name', 'Horacio Manuel Cartes Jara');
        $horacioId = $client->store($horacio);

        $tsegaye = $client->dispense("vertex");
        $tsegaye->set('name', 'Tsegaye Kebede');
        $tsegayeId = $client->store($tsegaye);

        $barack = $client->dispense("vertex");
        $barack->set('name', 'Barack Obama');
        $barackId = $client->store($barack);

        $friend1 = $barack->relateTo($horacio, "friend");
        $client->store($friend1);

        $friend2 = $tsegaye->relateTo($horacio, "friend");
        $client->store($friend2);

        $pod = $horacio->getPod();

        $inboundEdges = $pod->getInboundEdges(null, 'FILTER myplaceholder.`$label` IN [@friend] && myplaceholder._from == @barack', array("friend" => "friend", "barack" => $barack->getId()), "myplaceholder");

        $this->assertInternalType('array', $inboundEdges, "The result set should be an array");
        $this->assertCount(1, $inboundEdges, "The result set should only contain 1 result");

        $result = reset($inboundEdges);
        $this->assertInstanceOf('Paradox\pod\Edge', $result->getPod(), 'The inner pod should be an instance of Paradox\pod\Edge');
        $this->assertEquals($horacio->getId(), $result->getTo()->getId(), "The to vertex of the edge does not match the to vertex we queried from");
    }

    /**
     * @covers Paradox\pod\Vertex::getOutboundEdges
     */
    public function testGetOutboundEdges()
    {
        //Setup
        $client = $this->getClient();

        $horacio = $client->dispense("vertex");
        $horacio->set('name', 'Horacio Manuel Cartes Jara');
        $horacioId = $client->store($horacio);

        $tsegaye = $client->dispense("vertex");
        $tsegaye->set('name', 'Tsegaye Kebede');
        $tsegayeId = $client->store($tsegaye);

        $barack = $client->dispense("vertex");
        $barack->set('name', 'Barack Obama');
        $barackId = $client->store($barack);

        $friend1 = $barack->relateTo($horacio, "friend");
        $client->store($friend1);

        $friend2 = $tsegaye->relateTo($horacio, "friend");
        $client->store($friend2);

        $pod = $barack->getPod();

        $outboundEdges = $pod->getOutboundEdges(null, 'FILTER myplaceholder.`$label` IN [@friend] && myplaceholder._to == @horacio', array("friend" => "friend", "horacio" => $horacio->getId()), "myplaceholder");

        $this->assertInternalType('array', $outboundEdges, "The result set should be an array");
        $this->assertCount(1, $outboundEdges, "The result set should only contain 1 result");

        $result = reset($outboundEdges);
        $this->assertInstanceOf('Paradox\pod\Edge', $result->getPod(), 'The inner pod should be an instance of Paradox\pod\Edge');
        $this->assertEquals($barack->getId(), $result->getFrom()->getId(), "The to vertex of the edge does not match the to vertex we queried from");
    }

    /**
     * @covers Paradox\pod\Vertex::getEdges
     */
    public function testGetEdges()
    {
        //Setup
        $client = $this->getClient();

        $horacio = $client->dispense("vertex");
        $horacio->set('name', 'Horacio Manuel Cartes Jara');
        $horacioId = $client->store($horacio);

        $tsegaye = $client->dispense("vertex");
        $tsegaye->set('name', 'Tsegaye Kebede');
        $tsegayeId = $client->store($tsegaye);

        $barack = $client->dispense("vertex");
        $barack->set('name', 'Barack Obama');
        $barackId = $client->store($barack);

        $friend1 = $barack->relateTo($horacio, "friend");
        $client->store($friend1);

        $friend2 = $tsegaye->relateTo($horacio, "friend");
        $client->store($friend2);

        $friend3 = $horacio->relateTo($barack, "friend");
        $client->store($friend3);

        $pod = $horacio->getPod();

        $edges = $pod->getEdges(null, 'FILTER myplaceholder.`$label` IN [@friend]', array("friend" => "friend"), "myplaceholder");

        $this->assertInternalType('array', $edges, "The result set should be an array");
        $this->assertCount(3, $edges, "The result set should only contain 3 results");

        foreach ($edges as $edge) {
            $this->assertInstanceOf('Paradox\pod\Edge', $edge->getPod(), 'The inner pod should be an instance of Paradox\pod\Edge');
            $this->assertContains($horacio->getId(), array($edge->getTo()->getId(), $edge->getFrom()->getId()), "The to vertex or from vertex of the edge does not match the vertex we queried from");
        }
    }

    /**
     * @covers Paradox\pod\Vertex::getNeighbours
     */
    public function testGetNeighbours()
    {
        //Setup
        $client = $this->getClient();

        $horacio = $client->dispense("vertex");
        $horacio->set('name', 'Horacio Manuel Cartes Jara');
        $horacioId = $client->store($horacio);

        $tsegaye = $client->dispense("vertex");
        $tsegaye->set('name', 'Tsegaye Kebede');
        $tsegayeId = $client->store($tsegaye);

        $barack = $client->dispense("vertex");
        $barack->set('name', 'Barack Obama');
        $barackId = $client->store($barack);

        $friend1 = $barack->relateTo($horacio, "friend");
        $client->store($friend1);

        $friend2 = $tsegaye->relateTo($horacio, "friend");
        $client->store($friend2);

        $friend3 = $horacio->relateTo($barack, "friend");
        $client->store($friend3);

        $pod = $horacio->getPod();

        $neighbours = $pod->getNeighbours("any", null, 'FILTER myplaceholder.edge.`$label` IN [@friend]', array("friend" => "friend"), "myplaceholder");

        $this->assertInternalType('array', $neighbours, "The result set should be an array");
        $this->assertCount(2, $neighbours, "The result set should only contain 2 results");

        foreach ($neighbours as $neighbour) {
            $this->assertInstanceOf('Paradox\pod\Vertex', $neighbour->getPod(), 'The inner pod should be an instance of Paradox\pod\Vertex');
            $this->assertContains($neighbour->getId(), array($tsegaye->getId(), $barack->getId()), "The neighbours are not the expected neighbours");
        }
    }

    /**
     * @covers Paradox\pod\Vertex::toDriverDocument
     */
    public function testToDriverDocument()
    {
        $this->vertex->setId('mycollection/123456');
        $this->vertex->setRevision('myrevision');
        $this->vertex->set('mykey', 'myvalue');

        $converted = $this->vertex->toDriverDocument();
        $this->assertInstanceOf('triagens\ArangoDb\Vertex', $converted, 'The converted edge is not of type \triagens\ArangoDb\triagens\ArangoDb\Vertex');
        $this->assertEquals('mycollection/123456', $converted->getInternalId(), "The converted vertex's id does not match");
        $this->assertEquals('myrevision', $converted->getRevision(), "The converted vertex's revision does not match");
        $this->assertEquals('myvalue', $converted->get('mykey'), "The converted vertex's data does not match");
    }
}
