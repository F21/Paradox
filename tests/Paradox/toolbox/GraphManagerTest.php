<?php
namespace tests\Paradox\toolbox;
use tests\Base;
use Paradox\toolbox\GraphManager;
use Paradox\exceptions\GraphManagerException;
use Paradox\AModel;
use Paradox\pod\Vertex;

/**
 * Tests for the graph manager.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class GraphManagerTest extends Base
{
    /**
     * The collection name for this test case.
     * @var string
     */
    protected $collectionName = 'GraphManagerTestCollection';

    /**
     * The graph name for this test case.
     * @var string
     */
    protected $graphName = 'GraphManagerTestGraph';

    /**
     * Stores the graph manager.
     * @var GraphManager
     */
    protected $graphManager;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $client = $this->getClient();

        //Try to delete any leftovers
        try {
            $client->deleteCollection($this->collectionName);
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteGraph($this->graphName);
        } catch (\Exception $e) {
            //Ignore any errors
        }

        $client->createCollection($this->collectionName);
        $client->createGraph($this->graphName);

        //Setup the data
        $client->useConnection($this->graphName);

        $john = $client->dispense("vertex");
        $john->set('name', 'john smith');
        $client->store($john);

        $david = $client->dispense("vertex");
        $david->set('name', 'david jackson');
        $client->store($david);

        $barack = $client->dispense("vertex");
        $barack->set('name', 'barack obama');
        $client->store($barack);

        $gaga = $client->dispense("vertex"); //Lady gaga has no friends
        $gaga->set('name', "lady gaga");
        $client->store($gaga);

        $friends1 = $john->relateTo($david, "friends");
        $friends1->set('yearsKnown', 5);
        $client->store($friends1);

        $friends2 = $john->relateTo($barack, "friends");
        $friends2->set('yearsKnown', 2);
        $client->store($friends2);

        $friends3 = $barack->relateTo($john, "friends");
        $friends3->set('yearsKnown', 2);
        $client->store($friends3);

        $this->graphManager = $this->getGraphManager();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $client = $this->getClient();

        try {
            $client->deleteCollection($this->collectionName);
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteGraph($this->graphName);
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteGraph($this->graphName . 'TestGraph');
        } catch (\Exception $e) {
            //Ignore any errors
        }
    }

    /**
     * @covers Paradox\toolbox\GraphManager::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\GraphManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $property = $reflectionClass->getProperty('_toolbox');
        $property->setAccessible(true);

        //We need to create an empty object to pass to
        //ReflectionProperty's getValue method
        $manager = new GraphManager($this->getClient()->getToolbox());

        $this->assertInstanceOf('Paradox\Toolbox', $property->getValue($manager), 'GraphManager constructor did not store a Paradox\Toolbox.');
    }

    /**
     * Convinence function to get the finder
     */
    protected function getGraphManager($graph = null)
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $graph);

        return $client->getToolbox()->getGraphManager();
    }

    /**
     * @covers Paradox\toolbox\GraphManager::createGraph
     * @covers Paradox\toolbox\GraphManager::DeleteGraph
     */
    public function testCreateAndDeleteGraph()
    {
        $this->graphManager->createGraph($this->graphName . 'TestGraph');
        $this->graphManager->deleteGraph($this->graphName . 'TestGraph');
    }

    /**
     * @covers Paradox\toolbox\GraphManager::createGraph
     */
    public function testCreateInvalidGraph()
    {
        try {
            $this->graphManager->createGraph('!&@*##');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\GraphManagerException', $e, 'Exception thrown was not a Paradox\exceptions\GraphManagerException');

            return;
        }

        $this->fail("Tried to create graph with invalid name, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::deleteGraph
     */
    public function testDeleteInvalidGraph()
    {
        try {
            $this->graphManager->deleteGraph('GraphThatDoesNotExist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\GraphManagerException', $e, 'Exception thrown was not a Paradox\exceptions\GraphManagerException');

            return;
        }

        $this->fail("Tried to delete a graph that does not exist, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getGraphInfo
     */
    public function testGetGraphInfo()
    {
        $graphInfo = $this->graphManager->getGraphInfo($this->graphName);

        $this->assertInternalType('array', $graphInfo, "The graph info should be an array");
        $this->assertNotEmpty($graphInfo, "The graph info should not be empty");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getGraphInfo
     */
    public function testGetGraphInfoOnInvalidGraph()
    {
        try {
            $this->graphManager->getGraphInfo('GraphThatDoesNotExist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\GraphManagerException', $e, 'Exception thrown was not a Paradox\exceptions\GraphManagerException');

            return;
        }

        $this->fail("Tried to get info on a graph that does not exist, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getInboundEdges
     */
    public function testGetInboundEdges()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $barack = $client->findOne("vertex", "doc.name == @name", array('name' => 'barack obama'));

        $edges = $manager->getInboundEdges($barack, "friends");

        $this->assertInternalType('array', $edges, "Returned list of inbound edges should be an array");
        $this->assertCount(1, $edges, "The number of inbound edges should be 1");

        $edge = reset($edges);

        $this->assertInstanceOf('Paradox\AModel', $edge, 'The edge in the result list is not of type Paradox\AModel');
        $this->assertEquals($barack->getId(), $edge->getTo()->getId(), 'The "to" property does not point to the vertex');
        $this->assertNotEquals($barack->getId(), $edge->getFrom()->getId(), 'The "from" property should not point to the vertex');
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getInboundEdges
     */
    public function testGetInboundEdgesWithAQLAndNoLabelFilter()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $barack = $client->findOne("vertex", "doc.name == @name", array('name' => 'barack obama'));

        $edges = $manager->getInboundEdges($barack, null, 'FILTER doc.`$label` == @friends', array('friends' => 'friends'));

        $this->assertInternalType('array', $edges, "Returned list of inbound edges should be an array");
        $this->assertCount(1, $edges, "The number of inbound edges should be 1");

        $edge = reset($edges);

        $this->assertInstanceOf('Paradox\AModel', $edge, 'The edge in the result list is not of type Paradox\AModel');
        $this->assertEquals($barack->getId(), $edge->getTo()->getId(), 'The "to" property does not point to the vertex');
        $this->assertNotEquals($barack->getId(), $edge->getFrom()->getId(), 'The "from" property should not point to the vertex');
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getInboundEdges
     */
    public function testGetInboundEdgesOnNewVertex()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $newVertex = $client->dispense('vertex');

        //Do not save the vertex

        $edges = $manager->getInboundEdges($newVertex, "friends");

        $this->assertInternalType('array', $edges, "Returned list of inbound edges should be an array");
        $this->assertEmpty($edges, "Returned list of inbound edges should be empty");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getInboundEdges
     */
    public function testGetInboundEdgesOnVertexWithNoInboundEdges()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $gaga = $client->findOne("vertex", "doc.name == @name", array('name' => 'lady gaga'));

        $edges = $manager->getInboundEdges($gaga, "friends");

        $this->assertInternalType('array', $edges, "Returned list of inbound edges should be an array");
        $this->assertEmpty($edges, "Returned list of inbound edges should be empty");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getInboundEdges
     */
    public function testGetInboundEdgesOnVertexWithInvalidAQL()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $gaga = $client->findOne("vertex", "doc.name == @name", array('name' => 'lady gaga'));

        try {
            $edges = $manager->getInboundEdges($gaga, null, "x == x");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\GraphManagerException', $e, 'Exception thrown was not a Paradox\exceptions\GraphManagerException');

            return;
        }

        $this->fail("Tried filter inbound edges with invalid AQL, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getOutboundEdges
     */
    public function testGetOutboundEdges()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $john = $client->findOne("vertex", "doc.name == @name", array('name' => 'john smith'));

        $edges = $manager->getOutboundEdges($john, "friends");

        $this->assertInternalType('array', $edges, "Returned list of outbound edges should be an array");
        $this->assertCount(2, $edges, "The number of outbound edges should be 2");

           foreach ($edges as $id => $edge) {
               $this->assertInstanceOf('Paradox\AModel', $edge, 'The edge in the result list is not of type Paradox\AModel');
            $this->assertEquals($john->getId(), $edge->getFrom()->getId(), 'The "from" property does not point to the vertex');
            $this->assertNotEquals($john->getId(), $edge->getTo()->getId(), 'The "to" property should not point to the vertex');
           }
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getOutboundEdges
     */
    public function testGetOutboundEdgesWithAQLAndNoLabelFilter()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $john = $client->findOne("vertex", "doc.name == @name", array('name' => 'john smith'));

        $edges = $manager->getOutboundEdges($john, null, 'FILTER mydoc.`$label` == @friends', array('friends' => 'friends'), "mydoc");

        $this->assertInternalType('array', $edges, "Returned list of outbound edges should be an array");
        $this->assertCount(2, $edges, "The number of outbound edges should be 2");

        foreach ($edges as $id => $edge) {
            $this->assertInstanceOf('Paradox\AModel', $edge, 'The edge in the result list is not of type Paradox\AModel');
            $this->assertEquals($john->getId(), $edge->getFrom()->getId(), 'The "from" property does not point to the vertex');
            $this->assertNotEquals($john->getId(), $edge->getTo()->getId(), 'The "to" property should not point to the vertex');
        }
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getOutboundEdges
     */
    public function testGetOutboundEdgesOnNewVertex()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $newVertex = $client->dispense('vertex');

        //Do not save the vertex

        $edges = $manager->getOutboundEdges($newVertex, "friends");

        $this->assertInternalType('array', $edges, "Returned list of inbound edges should be an array");
        $this->assertEmpty($edges, "Returned list of inbound edges should be empty");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getOutboundEdges
     */
    public function testGetOutboundEdgesOnVertexWithNoOutboundEdges()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $gaga = $client->findOne("vertex", "doc.name == @name", array('name' => 'lady gaga'));

        $edges = $manager->getOutboundEdges($gaga, "friends");

        $this->assertInternalType('array', $edges, "Returned list of inbound edges should be an array");
        $this->assertEmpty($edges, "Returned list of inbound edges should be empty");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getOutboundEdges
     */
    public function testGetOutboundEdgesOnVertexWithInvalidAQL()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $gaga = $client->findOne("vertex", "doc.name == @name", array('name' => 'lady gaga'));

        try {
            $edges = $manager->getOutboundEdges($gaga, null, "x == x");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\GraphManagerException', $e, 'Exception thrown was not a Paradox\exceptions\GraphManagerException');

            return;
        }

        $this->fail("Tried filter outbound edges with invalid AQL, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getEdges
     */
    public function testGetEdges()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $barack = $client->findOne("vertex", "doc.name == @name", array('name' => 'barack obama'));

        $edges = $manager->getEdges($barack, "friends");

        $this->assertInternalType('array', $edges, "Returned list of edges should be an array");
        $this->assertCount(2, $edges, "The number of outbound edges should be 2");

           foreach ($edges as $id => $edge) {
               $this->assertInstanceOf('Paradox\AModel', $edge, 'The edge in the result list is not of type Paradox\AModel');
            $this->assertContains($barack->getId(), array($edge->getFrom()->getId(), $edge->getTo()->getId()), 'One end of the edge should be connected to the vertex');
           }
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getEdges
     */
    public function testGetEdgesWithAQLAndNoLabelFilter()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $barack = $client->findOne("vertex", "doc.name == @name", array('name' => 'barack obama'));

        $edges = $manager->getEdges($barack, null, 'FILTER mydoc.`$label` == @friends', array('friends' => 'friends'), 'mydoc');

        $this->assertInternalType('array', $edges, "Returned list of edges should be an array");
        $this->assertCount(2, $edges, "The number of outbound edges should be 2");

        foreach ($edges as $id => $edge) {
            $this->assertInstanceOf('Paradox\AModel', $edge, 'The edge in the result list is not of type Paradox\AModel');
            $this->assertContains($barack->getId(), array($edge->getFrom()->getId(), $edge->getTo()->getId()), 'One end of the edge should be connected to the vertex');
        }
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getEdges
     */
    public function testGetEdgesOnNewVertex()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $newVertex = $client->dispense('vertex');

        //Do not save the vertex

        $edges = $manager->getEdges($newVertex, "friends");

        $this->assertInternalType('array', $edges, "Returned list of inbound edges should be an array");
        $this->assertEmpty($edges, "Returned list of inbound edges should be empty");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getEdges
     */
    public function testGetEdgesOnVertexWithNoEdges()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $gaga = $client->findOne("vertex", "doc.name == @name", array('name' => 'lady gaga'));

        $edges = $manager->getEdges($gaga, "friends");

        $this->assertInternalType('array', $edges, "Returned list of edges should be an array");
        $this->assertEmpty($edges, "Returned list of edges should be empty");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getEdges
     */
    public function testGetEdgesOnVertexWithInvalidAQL()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $gaga = $client->findOne("vertex", "doc.name == @name", array('name' => 'lady gaga'));

        try {
            $edges = $manager->getEdges($gaga, null, "x == x");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\GraphManagerException', $e, 'Exception thrown was not a Paradox\exceptions\GraphManagerException');

            return;
        }

        $this->fail("Tried filter edges with invalid AQL, but no exception was thrown");

    }

    /**
     * @covers Paradox\toolbox\GraphManager::getNeighbours
     */
    public function testGetNeighbours()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $barack = $client->findOne("vertex", "doc.name == @name", array('name' => 'barack obama'));

        $vertices = $manager->getNeighbours($barack, "any", "friends");

        $this->assertInternalType('array', $vertices, "Returned list of vertices should be an array");
        $this->assertCount(1, $vertices, "The number of vertices should be 1");

        foreach ($vertices as $id => $vertex) {
            $this->assertInstanceOf('Paradox\AModel', $vertex, 'The edge in the result list is not of type Paradox\AModel');
            $this->assertNotEquals($barack->getId(), $vertex->getId(), 'The vertex should not be the same vertex as the one querying for neighbours');
        }
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getNeighbours
     */
    public function testGetInboundNeighbours()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $barack = $client->findOne("vertex", "doc.name == @name", array('name' => 'barack obama'));

        $vertices = $manager->getNeighbours($barack, "in", "friends");

        $this->assertInternalType('array', $vertices, "Returned list of vertices should be an array");
        $this->assertCount(1, $vertices, "The number of vertices should be 1");

        foreach ($vertices as $id => $vertex) {
            $this->assertInstanceOf('Paradox\AModel', $vertex, 'The edge in the result list is not of type Paradox\AModel');
            $this->assertNotEquals($barack->getId(), $vertex->getId(), 'The vertex should not be the same vertex as the one querying for neighbours');
        }
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getNeighbours
     */
    public function testGetOutboundNeighbours()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $barack = $client->findOne("vertex", "doc.name == @name", array('name' => 'barack obama'));

        $vertices = $manager->getNeighbours($barack, "out", "friends");

        $this->assertInternalType('array', $vertices, "Returned list of vertices should be an array");
        $this->assertCount(1, $vertices, "The number of vertices should be 1");

        foreach ($vertices as $id => $vertex) {
            $this->assertInstanceOf('Paradox\AModel', $vertex, 'The edge in the result list is not of type Paradox\AModel');
            $this->assertNotEquals($barack->getId(), $vertex->getId(), 'The vertex should not be the same vertex as the one querying for neighbours');
        }
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getNeighbours
     */
    public function testGetNeighboursWithAQLAndNoLabelFilter()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $barack = $client->findOne("vertex", "doc.name == @name", array('name' => 'barack obama'));

        $vertices = $manager->getNeighbours($barack, "any", null, 'FILTER mydoc.edge.`$label` == @friends', array('friends' => 'friends'), "mydoc");

        $this->assertInternalType('array', $vertices, "Returned list of vertices should be an array");
        $this->assertCount(1, $vertices, "The number of vertices should be 1");

        foreach ($vertices as $id => $vertex) {
            $this->assertInstanceOf('Paradox\AModel', $vertex, 'The edge in the result list is not of type Paradox\AModel');
            $this->assertNotEquals($barack->getId(), $vertex->getId(), 'The vertex should not be the same vertex as the one querying for neighbours');
        }
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getNeighbours
     */
    public function testGetNeighboursOnNewVertex()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $newVertex = $client->dispense('vertex');

        //Do not save the vertex

        $vertices = $manager->getNeighbours($newVertex, "any", "friends");

        $this->assertInternalType('array', $vertices, "Returned list of inbound vertices should be an array");
        $this->assertEmpty($vertices, "Returned list of inbound vertices should be empty");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getNeighbours
     */
    public function testGetNeighboursOnVertexWithNoEdges()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $gaga = $client->findOne("vertex", "doc.name == @name", array('name' => 'lady gaga'));

        $neighbours = $manager->getNeighbours($gaga, "any", "friends");

        $this->assertInternalType('array', $neighbours, "Returned list of vertices should be an array");
        $this->assertEmpty($neighbours, "Returned list of vertices should be empty");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getNeighbours
     */
    public function testGetNeighboursOnVertexWithInvalidAQL()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getGraphManager();

        $gaga = $client->findOne("vertex", "doc.name == @name", array('name' => 'lady gaga'));

        try {
            $neighbours = $manager->getNeighbours($gaga, "any", null, "x == x");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\GraphManagerException', $e, 'Exception thrown was not a Paradox\exceptions\GraphManagerException');

            return;
        }

        $this->fail("Tried filter neighbours with invalid AQL, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getVertexId
     */
    public function testGetVertexIdWithModel()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\GraphManager');

        $method = $reflectionClass->getMethod('getVertexId');
        $method->setAccessible(true);

        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = new GraphManager($client->getToolbox());

        $model = $client->findOne("vertex", "doc.name == @name", array('name' => 'lady gaga'));

        $vertexId = $method->invoke($manager, $model);

        $this->assertEquals($model->getPod()->getId(), $vertexId, "getVertexId() did not retrieve the correct id from the model");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getVertexId
     */
    public function testGetVertexIdWithPod()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\GraphManager');

        $method = $reflectionClass->getMethod('getVertexId');
        $method->setAccessible(true);

        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = new GraphManager($client->getToolbox());

        $model = $client->findOne("vertex", "doc.name == @name", array('name' => 'lady gaga'));

        $vertexId = $method->invoke($manager, $model->getPod());

        $this->assertEquals($model->getPod()->getId(), $vertexId, "getVertexId() did not retrieve the correct id from the pod");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getVertexId
     */
    public function testGetVertexIdWithString()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\GraphManager');

        $method = $reflectionClass->getMethod('getVertexId');
        $method->setAccessible(true);

        $manager = new GraphManager($this->getClient()->getToolbox());

        $id = $this->graphName . '/123456';

        $vertexId = $method->invoke($manager, $id);

        $this->assertEquals($id, $vertexId, "getVertexId() did not return the string as the id");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::getVertexId
     */
    public function testGetVertexIdWithInvalidData()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\GraphManager');

        $method = $reflectionClass->getMethod('getVertexId');
        $method->setAccessible(true);

        $manager = new GraphManager($this->getClient()->getToolbox());

        try {
            $vertexId = $method->invoke($manager, new \stdClass());
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\GraphManagerException', $e, 'Exception thrown was not a Paradox\exceptions\GraphManagerException');

            return;
        }

        $this->fail("Using testGetVertexId() with an argument that is not an AModel, Document (pod) or string did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\GraphManager::convertToPods
     */
    public function testConvertToPods()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);

        $reflectionClass = new \ReflectionClass('Paradox\toolbox\GraphManager');

        $method = $reflectionClass->getMethod('convertToPods');
        $method->setAccessible(true);

        $graphManager = new GraphManager($client->getToolbox());

        $vertex = $client->findOne("vertex", 'doc.name == @name', array('name' => 'barack obama'));

        $queryResult = $client->getAll("FOR u in EDGES(@@collection, @vid, @direction) return u",
                array('@collection' => $client->getToolbox()->getEdgeCollectionName(), 'vid' => $vertex->getPod()->getId(),
                      'direction' => 'outbound'
        ));

        $pods = $method->invoke($graphManager, "edge", $queryResult);

        $this->assertInternalType('array', $pods, "Converted pods are not in an array");
        $this->assertCount(1, $pods, "Only 1 result is expected");

        $podResult = reset($pods);
        $this->assertInstanceOf('Paradox\AModel', $podResult, 'Result is not of type Paradox\AModel');
        $this->assertInstanceOf('Paradox\pod\Edge', $podResult->getPod(), 'The inner pod should be of type Paradox\pod\Edge');

        $singleResult = reset($queryResult);

        $this->assertEquals($singleResult['_id'], $podResult->getId(), "The id of the converted pod and the query result does not match");
        $this->assertEquals($singleResult['_key'], $podResult->getKey(), "The key of the converted pod and the query result does not match");
        $this->assertEquals($singleResult['_rev'], $podResult->getRevision(), "The revision of the converted pod and the query result does not match");

        foreach ($singleResult as $key => $value) {

            if (substr($key, 0, 1) != "_") {
                $this->assertEquals($value, $podResult->get($key), "The value of the key in the converted pod does not match the query's value");
            }
        }
    }
}
