<?php
namespace tests\Paradox;
use tests\Base;
use Paradox\exceptions\ClientException;
use Paradox\Client;
use Paradox\AModel;

/**
 * Tests for the client.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class ClientTest extends Base
{
    /**
     * The collection name for this test case.
     * @var string
     */
    protected $collectionName = 'ClientTestCollection';

    /**
     * The graph name for this test case.
     * @var string
     */
    protected $graphName = 'ClientTestGraph';

    /**
     * Stores an instance of the client.
     * @var Client
     */
    protected $client;

    /**
     * Stores an instance of the client that manages a graph.
     * @var Client
     */
    protected $graphClient;

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

        try {
            $client->deleteAQLFunction("paradoxtest:helloworld");
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $client->deleteAQLFunction("paradoxtest:helloworld2");
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $client->deleteAQLFunction("paradoxtest1:helloworld");
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $client->deleteAQLFunction("paradoxtest2:helloworld");
        } catch (\Exception $e) {
            //Ignore the error
        }

        $client->createCollection($this->collectionName);
        $client->createGeoIndex($this->collectionName, 'geofield');
        $client->createFulltextIndex($this->collectionName, 'bio');

        //Setup the test data
        $document1 = $client->dispense($this->collectionName);
        $document1->set('name', 'Horacio Manuel Cartes Jara');
        $document1->set('bio', "Cartes' father was the owner of a Cessna aircraft franchise holding company and Horacio Cartes studied aeronautical engineering in the United States. At the age of 19, he started a currency exchange business which grew into the Banco Amambay. Over the following years, Cartes bought or helped establish 25 companies including Tabesa, the country's biggest cigarette manufacturer, and a major fruit juice bottling company.");
        $document1->set('geofield', array(48.1, 48.1));
        $client->store($document1);

        $document2 = $client->dispense($this->collectionName);
        $document2->set('name', 'Tsegaye Kebede');
        $document2->set('bio', "In the 2009 season he established himself as one of Ethiopia's top athletes: he came second in the London Marathon and at his first World Championships in Athletics he took the bronze medal in the marathon. He retained his Fukuoka Marathon title at the end of 2009, running the fastest ever marathon race in Japan. He won the 2010 London Marathon, his first World Marathon Major and the 2013 London Marathon.");
        $document2->set('geofield', array(48.1, 48.1));
        $client->store($document2);

        $document3 = $client->dispense($this->collectionName);
        $document3->set('name', 'Giorgio Napolitano');
        $document3->set('bio', "Giorgio Napolitano was born in Naples, Italy. In 1942, he matriculated at the University of Naples Federico II. He adhered to the local University Fascist Youth, where he met his core group of friends, who shared his membership in the Italian fascism. As he would later state, the group was in fact a true breeding ground of anti-fascist intellectual energies, disguised and to a certain extent tolerated.");
        $document3->set('geofield', array(48.2, 48.2));
        $client->store($document3);

        $document4 = $client->dispense($this->collectionName);
        $document4->set('name', 'Priscah Jeptoo');
        $document4->set('bio', "She began competing at top level competitions in 2008 and made the top ten women at the Saint Silvester Road Race that year. Her 2009 began with two wins in Portugal, at the Douro-Tal Half Marathon and then the Corrida Festas Cidade do Porto 15K race. These preceded a course record-breaking run at the Porto Marathon in November, as she recorded a time of 2:30:40 hours for her debut effort.");
        $document4->set('geofield', array(50, 50));
        $client->store($document4);

        $client->createGraph($this->graphName);

        $this->client = $this->getClient();
        $this->graphClient = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
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
            $client->deleteGraph('mynewtestgraph');
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteCollection('mytestcollection');
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteCollection('mytestcollection2');
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteUser('testuser');
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteAQLFunction("paradoxtest:helloworld");
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $client->deleteAQLFunction("paradoxtest:helloworld2");
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $client->deleteAQLFunction("paradoxtest1:helloworld");
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $client->deleteAQLFunction("paradoxtest2:helloworld");
        } catch (\Exception $e) {
            //Ignore the error
        }
    }

    /**
     * @covers Paradox\Client::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\Client');

        //Then we need to get the property we wish to test
        //and make it accessible
        $toolboxes = $reflectionClass->getProperty('_toolboxes');
        $toolboxes->setAccessible(true);

        $currentConnection = $reflectionClass->getProperty('_currentConnection');
        $currentConnection->setAccessible(true);

        $client = new Client('tcp://someendpoint', 'someuser', 'somepassword');

        //Check the toolbox
        $this->assertArrayHasKey('default', $toolboxes->getValue($client), 'Setting a connection when instantiating the client should add it under the "default" key');

        $toolbox = $toolboxes->getvalue($client)['default'];

        $this->assertInstanceOf('Paradox\Toolbox', $toolbox, 'The connection should be stored in a Paradox\Toolbox');
        $this->assertEquals('tcp://someendpoint', $toolbox->getEndpoint(), "The endpoint does not match");
        $this->assertEquals('someuser', $toolbox->getUsername(), "The username does not match");
        $this->assertEquals('somepassword', $toolbox->getPassword(), "The password does not match");

        //Check the current connection
        $this->assertEquals('default', $currentConnection->getValue($client), 'The current connection is not set to "default"');
    }

    /**
     * @covers Paradox\Client::addConnection
     */
    public function testAddConnection()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\Client');

        //Then we need to get the property we wish to test
        //and make it accessible
        $toolboxes = $reflectionClass->getProperty('_toolboxes');
        $toolboxes->setAccessible(true);

        $this->client->addConnection('newconnection', 'tcp://someendpoint', 'someuser', 'somepassword', $this->graphName);

        //Check the toolbox
        $this->assertArrayHasKey('newconnection', $toolboxes->getValue($this->client), 'The "newconnection" connection was not added');

        $toolbox = $toolboxes->getvalue($this->client)['newconnection'];

        $this->assertInstanceOf('Paradox\Toolbox', $toolbox, 'The connection should be stored in a Paradox\Toolbox');
        $this->assertEquals('tcp://someendpoint', $toolbox->getEndpoint(), "The endpoint does not match");
        $this->assertEquals('someuser', $toolbox->getUsername(), "The username does not match");
        $this->assertEquals('somepassword', $toolbox->getPassword(), "The password does not match");
        $this->assertEquals($this->graphName, $toolbox->getGraph(), "The graph does not match");
    }

    /**
     * @covers Paradox\Client::useConnection
     */
    public function testUseConnection()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\Client');

        //Then we need to get the property we wish to test
        //and make it accessible
        $currentConnection = $reflectionClass->getProperty('_currentConnection');
        $currentConnection->setAccessible(true);

        $this->client->addConnection('newconnection', 'tcp://someendpoint', 'someuser', 'somepassword', $this->graphName);
        $this->client->useConnection('newconnection');

        $this->assertEquals('newconnection', $currentConnection->getValue($this->client), 'The current connection should be set to "newconnection"');
    }

    /**
     * @covers Paradox\Client::useConnection
     */
    public function testUseConnectionWithInvalidName()
    {
        try {
            $this->client->useConnection('nonexistentconnection');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ClientException', $e, 'Exception thrown was not a Paradox\exceptions\ClientException');

            return;
        }

        $this->fail('Trying to use a connection that does not exist did not throw an exception');
    }

    /**
     * @covers Paradox\Client::getCurrentConnection
     */
    public function testGetCurrentConnection()
    {
        $client = $this->getClient();
        $client->addConnection('newtestconnection', 'tcp://localhost:8529', 'root', '');
        $client->useConnection('newtestconnection');

        $currentConnection = $client->getCurrentConnection();

        $this->assertEquals('newtestconnection', $currentConnection, 'Current connection is not "newtestconnection"');
    }

    /**
     * @covers Paradox\Client::getCurrentConnection
     */
    public function testGetNonexistentConnection()
    {
        $client = $this->getClient();

        try {
            $client->useConnection('connectionthatdoesnotexist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ClientException', $e, 'Exception was not of the type Paradox\exceptions\ClientException');

            return;
        }

        $this->fail('Exception was expected when getting a non-exisistent connection.');
    }

    /**
     * @covers Paradox\Client::getToolbox
     */
    public function testGetToolbox()
    {
        $client = $this->getClient();
        $client->addConnection('mynewconnection', 'tcp://localhost:8529', 'root', '');

        $toolbox = $client->getToolbox('mynewconnection');

        $this->assertInstanceOf('Paradox\ToolBox', $toolbox, 'Returned toobox was not of the type Paradox\Toolbox');
    }

    /**
     * @covers Paradox\Client::getToolbox
     */
    public function testGetNonexistentToolbox()
    {
        $client = $this->getClient();

        try {
            $toolbox = $client->getToolbox('nonexistentconnection');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ClientException', $e, 'Exception was not of the type Paradox\exceptions\ClientException');

            return;
        }

        $this->fail('Exception was expected when getting a non-exisistent toolbox.');
    }

    /**
     * @covers Paradox\Client::setModelFormatter
     */
    public function testSetInvalidModelFormatter()
    {
        $client = $this->getClient();

        $this->setExpectedException('PHPUnit_Framework_Error');

        $client->setModelFormatter(new \stdClass());
    }

    /**
     * @covers Paradox\Client::setModelFormatter
     */
    public function testSetModelFormatter()
    {
        $client = $this->getClient();

        $mockFormatter = $this->getMock('Paradox\IModelFormatter');

        $client->setModelFormatter($mockFormatter);
    }

    /**
     * @covers Paradox\Client::Dispense
     */
    public function testDispense()
    {
        $document = $this->client->dispense($this->collectionName);
        $this->assertInstanceOf('Paradox\AModel', $document, 'The dispense document should be a Paradox\AModel');
    }

    /**
     * @covers Paradox\Client::Store
     */
    public function testStore()
    {
        $document = $this->client->dispense($this->collectionName);
        $id = $this->client->store($document);

        $this->assertNotNull($id, "The id of a saved document should not be null");
    }

    /**
     * @covers Paradox\Client::Delete
     */
    public function testDelete()
    {
        $document = $this->client->dispense($this->collectionName);
        $id = $this->client->store($document);

        $this->assertNotNull($id, "The id of a saved document should not be null");

        $this->client->delete($document);

        $result = $this->client->load($this->collectionName, $id);

        $this->assertNull($result, "Retrieving a deleted document should return a null");
    }

    /**
     * @covers Paradox\Client::load
     */
    public function testLoad()
    {
        $document = $this->client->dispense($this->collectionName);
        $id = $this->client->store($document);

        $this->assertNotNull($id, "The id of a saved document should not be null");

        $result = $this->client->load($this->collectionName, $id);

        $this->assertEquals($document->getId(), $result->getId(), "The retrieved document should have the same id to the one that was stored");
    }

    /**
     * @covers Paradox\Client::getAll
     */
    public function testGetAll()
    {
        $result = $this->client->getAll("FOR doc in $this->collectionName RETURN doc");

        $this->assertInternalType('array', $result, "The result set should be an array");
        $this->assertCount(4, $result, "The result set should contain only 4 results");
    }

    /**
     * @covers Paradox\Client::getOne
     */
    public function testGetOne()
    {
        $result = $this->client->getOne("FOR doc in $this->collectionName RETURN doc");

        $this->assertInternalType('array', $result, "The result should be an array");
    }

    /**
     * @covers Paradox\Client::explain
     */
    public function testExplain()
    {
        $result = $this->client->explain("FOR doc in $this->collectionName RETURN doc");

        $this->assertInternalType('array', $result, "The result should be an array");
    }

    /**
     * @covers Paradox\Client::find
     */
    public function testFind()
    {
        $result = $this->client->find($this->collectionName, "u.name in [@tsegaye, @giorgio]", array('tsegaye' => 'Tsegaye Kebede', 'giorgio' => 'Giorgio Napolitano'), "u");

        $this->assertInternalType('array', $result, "The result set should be an array");
        $this->assertCount(2, $result, "The result set should contain only 2 results");
    }

    /**
     * @covers Paradox\Client::findAll
     */
    public function testFindAll()
    {
        $result = $this->client->findAll($this->collectionName, "FILTER u.name in [@tsegaye, @giorgio] LIMIT 1", array('tsegaye' => 'Tsegaye Kebede', 'giorgio' => 'Giorgio Napolitano'), "u");

        $this->assertInternalType('array', $result, "The result set should be an array");
        $this->assertCount(1, $result, "The result set should contain only 1 results");
    }

    /**
     * @covers Paradox\Client::findOne
     */
    public function testFindOne()
    {
        $result = $this->client->findOne($this->collectionName, "u.name in [@tsegaye, @giorgio]", array('tsegaye' => 'Tsegaye Kebede', 'giorgio' => 'Giorgio Napolitano'), "u");

        $this->assertInstanceOf('Paradox\AModel', $result, 'The result set should be an instance of Paradox\AModel');
    }

    /**
     * @covers Paradox\Client::getGraphInfo
     */
    public function testGetGraphInfo()
    {
        //Check that the graph exists
        $graphInfo = $this->client->getGraphInfo($this->graphName);

        $this->assertInternalType('array', $graphInfo, 'The graph info should be an array');
        $this->assertNotEmpty($graphInfo, 'The graph info should not be empty');
    }

    /**
     * @covers Paradox\Client::createGraph
     * @covers Paradox\Client::deleteGraph
     */
    public function testCreateAndDeleteGraph()
    {
        $this->client->createGraph('mynewtestgraph');

        //Check that the graph exists
        $graphInfo = $this->client->getGraphInfo('mynewtestgraph');

        $this->assertInternalType('array', $graphInfo, 'The graph info should be an array');
        $this->assertNotEmpty($graphInfo, 'The graph info should not be empty');

        //Delete the graph
        $this->client->deleteGraph('mynewtestgraph');

        try {
            $graphInfo = $this->client->getGraphInfo('mynewtestgraph');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\GraphManagerException', $e, 'Exception thrown was not a Paradox\exceptions\GraphManagerException');

            return;
        }

        $this->fail("Tried to get info on a graph that does not exist, but no exception was thrown");
    }

    /**
     * @covers Paradox\Client::getVertexCollectionName()
     */
    public function testGetVertexCollectionName()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);

        $this->assertEquals($this->graphName . 'VertexCollection', $client->getVertexCollectionName(), "The vertex collection name does not match");
    }

    /**
     * @covers Paradox\Client::getEdgeCollectionName()
     */
    public function testGetEdgeCollectionName()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);

        $this->assertEquals($this->graphName . 'EdgeCollection', $client->getEdgeCollectionName(), "The edge collection name does not match");
    }

    /**
     * @covers Paradox\Client::createCollection
     * @covers Paradox\Client::deleteCollection
     */
    public function testCreateAndDeleteCollection()
    {
        $this->client->createCollection('mytestcollection');

        $collectionInfo = $this->client->getCollectionInfo('mytestcollection');

        $this->assertInternalType('array', $collectionInfo, 'The collection info should be an array');
        $this->assertNotEmpty($collectionInfo, 'The collection info should not be empty');

        $this->client->deleteCollection('mytestcollection');

        try {
            $graphInfo = $this->client->getCollectionInfo('mytestcollection');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to get info on a collection that does not exist, but no exception was thrown");
    }

    /**
     * @covers Paradox\Client::renameCollection
     */
    public function testRenameCollection()
    {
        $this->client->createCollection('mytestcollection');

        $this->client->renameCollection('mytestcollection', 'mytestcollection2');

        $collectionInfo = $this->client->getCollectionInfo('mytestcollection2');
        $this->assertInternalType('array', $collectionInfo, 'The collection info should be an array');
        $this->assertNotEmpty($collectionInfo, 'The collection info should not be empty');

        $this->client->deleteCollection('mytestcollection2');

        try {
            $graphInfo = $this->client->getCollectionInfo('mytestcollection2');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to get info on a collection that does not exist, but no exception was thrown");
    }

    /**
     * @covers Paradox\Client::wipe
     */
    public function testWipe()
    {
        $this->assertEquals(4, $this->client->count($this->collectionName), "The collection should only contain 4 documents");

        $this->client->wipe($this->collectionName);

        $this->assertEquals(0, $this->client->count($this->collectionName), "The collection should contain no documents");
    }

    /**
     * @covers Paradox\Client::getCollectionInfo
     */
    public function testGetCollectionInfo()
    {
        $collectionInfo = $this->client->getCollectionInfo($this->collectionName);
        $this->assertInternalType('array', $collectionInfo, 'The collection info should be an array');
        $this->assertNotEmpty($collectionInfo, 'The collection info should not be empty');
    }

    /**
     * @covers Paradox\Client::getCollectionStatistics
     */
    public function testGetCollectionStatistics()
    {
        $collectionStatistics = $this->client->getCollectionStatistics($this->collectionName);
        $this->assertInternalType('array', $collectionStatistics, 'The collection info should be an array');
        $this->assertNotEmpty($collectionStatistics, 'The collection info should not be empty');
    }

    /**
     * @covers Paradox\Client::count
     */
    public function testCount()
    {
        $this->assertEquals(4, $this->client->count($this->collectionName), "The collection should only contain 4 documents");
    }

    /**
     * @covers Paradox\Client::listCollections
     */
    public function testListCollections()
    {
        $collections = $this->client->listCollections();

        $this->assertGreaterThanOrEqual(1, count($collections), "There should be at least 1 collection on the server");
    }

    /**
     * @covers Paradox\Client::listCollections
     */
    public function testListCollectionsWithInfo()
    {
        $collections = $this->client->listCollections(false, true);

        $this->assertGreaterThanOrEqual(1, count($collections), "There should be at least 1 collection on the server");

        foreach ($collections as $collection) {
            $this->assertInternalType('array', $collection, "The collection data should be an array");
        }
    }

    /**
     * @covers Paradox\Client::listCollections
     */
    public function testListCollectionsWithSystemCollections()
    {
        $collections = $this->client->listCollections(false);

        $this->assertGreaterThanOrEqual(1, count($collections), "There should be at least 1 collection on the server");

        $this->assertContains('_users', $collections, "The collections list should contain the _user system collection");
    }

    /**
     * @covers Paradox\Client::loadCollection
     */
    public function testLoadCollection()
    {
        $this->client->unloadCollection($this->collectionName);

        //Verify it is unloaded. Note that we list collections, because using getCollectionInfo() loads the collection.
        $collectionInfo = $this->client->listCollections(true, true);

        //Status 2 = unloaded
        $this->assertEquals(2, $collectionInfo[$this->collectionName]['status'], "The collection was not unloaded");

        //Load the collection
        $this->client->loadCollection($this->collectionName);

        //Verify it is loaded. Note that we list collections, because using getCollectionInfo() loads the collection.
        $collectionInfo = $this->client->listCollections(true, true);

        //Status 3 = loaded
        $this->assertEquals(3, $collectionInfo[$this->collectionName]['status'], "The collection was not loaded");
    }

    /**
     * @covers Paradox\Client::unloadCollection
     */
    public function testUnloadCollection()
    {
        $this->client->loadCollection($this->collectionName);

        //Verify it is loaded. Note that we list collections, because using getCollectionInfo() loads the collection.
        $collectionInfo = $this->client->listCollections(true, true);

        //Status 3 = loaded
        $this->assertEquals(3, $collectionInfo[$this->collectionName]['status'], "The collection was not loaded");

        //Unload the collection
        $this->client->unloadCollection($this->collectionName);

        //Verify it is unloaded. Note that we list collections, because using getCollectionInfo() loads the collection.
        $collectionInfo = $this->client->listCollections(true, true);

        //Status 2 = loaded
        $this->assertEquals(2, $collectionInfo[$this->collectionName]['status'], "The collection was not unloaded");
    }

    /**
     * @covers Paradox\Client::listIndices
     */
    public function testListIndices()
    {
        $indices = $this->client->listIndices($this->collectionName, true);

        $this->assertInternalType('array', $indices, "The indices info should be an array");
        $this->assertCount(3, $indices, "The collection should only have 3 indices");
    }

    /**
     * @covers Paradox\Client::createCapConstraint
     */
    public function testCreateCapConstraint()
    {
        $id = $this->client->createCapConstraint($this->collectionName, 10);

        $info = $this->client->getIndexInfo($this->collectionName, $id);

        $this->assertInternalType('array', $info, "The index we just created is missing");
        $this->assertEquals("cap", $info['type'], 'The type of the created index should be "cap"');
        $this->assertEquals(10, $info['size'], "The size of the created index does not match");
    }

    /**
     * @covers Paradox\Client::createGeoIndex
     */
    public function testCreateGeo1Index()
    {
        $id = $this->client->createGeoIndex($this->collectionName, 'mygeofield', true, true, true);

        $info = $this->client->getIndexInfo($this->collectionName, $id);

        $this->assertInternalType('array', $info, "The index we just created is missing");
        $this->assertEquals("geo1", $info['type'], 'The type of the created index should be "geo1"');
        $this->assertEquals('mygeofield', $info['fields'][0], "The field of the created index does not match");
        $this->assertTrue($info['geoJson'], 'The geoJson of the created index should be "true"');
        $this->assertTrue($info['constraint'], 'The constraint of the create index should be "true"');
        $this->assertTrue($info['ignoreNull'], 'The ignoreNull of the created index should be "true"');
    }

    /**
     * @covers Paradox\Client::createGeoIndex
     */
    public function testCreateGeo2Index()
    {
        $id = $this->client->createGeoIndex($this->collectionName, array('lat', 'long'), false, true, true);

        $info = $this->client->getIndexInfo($this->collectionName, $id);

        $this->assertInternalType('array', $info, "The index we just created is missing");
        $this->assertEquals("geo2", $info['type'], 'The type of the created index should be "geo2"');
        $this->assertEquals('lat', $info['fields'][0], "The first field of the created index does not match");
        $this->assertEquals('long', $info['fields'][1], "The second field of the created index does not match");
        $this->assertArrayNotHasKey('geoJson', $info, 'The geoJson of the created index should be "false"');
        $this->assertTrue($info['constraint'], 'The constraint of the create index should be "true"');
        $this->assertTrue($info['ignoreNull'], 'The ignoreNull of the created index should be "true"');
    }

    /**
     * @covers Paradox\Client::createHashIndex
     */
    public function testCreateHashIndex()
    {
        $id = $this->client->createHashIndex($this->collectionName, 'myfield', true);

        $info = $this->client->getIndexInfo($this->collectionName, $id);

        $this->assertInternalType('array', $info, "The index we just created is missing");
        $this->assertEquals("hash", $info['type'], 'The type of the created index should be "hash"');
        $this->assertEquals('myfield', $info['fields'][0], "The field of the created index does not match");
        $this->assertTrue($info['unique'], 'The unique of the create index should be "true"');
    }

    /**
     * @covers Paradox\Client::createFulltextIndex
     */
    public function testCreateFulltextIndex()
    {
        $id = $this->client->createFulltextIndex($this->collectionName, 'myfield', 10);

        $info = $this->client->getIndexInfo($this->collectionName, $id);

        $this->assertInternalType('array', $info, "The index we just created is missing");
        $this->assertEquals("fulltext", $info['type'], 'The type of the created index should be "fulltext"');
        $this->assertEquals('myfield', $info['fields'][0], "The field of the created index does not match");
        $this->assertEquals(10, $info['minLength'], 'The minLength of the created index does not match');
    }

    /**
     * @covers Paradox\Client::createSkipListIndex
     */
    public function testCreateSkipListIndex()
    {
        $id = $this->client->createSkipListIndex($this->collectionName, 'myfield', true);

        $info = $this->client->getIndexInfo($this->collectionName, $id);

        $this->assertInternalType('array', $info, "The index we just created is missing");
        $this->assertEquals("skiplist", $info['type'], 'The type of the created index should be "skiplist"');
        $this->assertEquals('myfield', $info['fields'][0], "The field of the created index does not match");
        $this->assertTrue($info['unique'], 'The unique of the created index should be "true"');
    }

    /**
     * @covers Paradox\Client::deleteIndex
     */
    public function testDeleteIndex()
    {
        $id = $this->client->createSkipListIndex($this->collectionName, 'myfield', true);

        //Check that the index is created
        $index = $this->client->getIndexInfo($this->collectionName, $id);
        $this->assertInternalType('array', $index, "The index info should be an array");

        //Delete
        $this->client->deleteIndex($this->collectionName, $id);

        //Check that the index is deleted
        $index = $this->client->getIndexInfo($this->collectionName, $id);
        $this->assertNull($index, "Since the index does not exist, a null is expected");
    }

    /**
     * @covers Paradox\Client::getIndexInfo
     */
    public function testGetIndexInfo()
    {
        $id = $this->client->createSkipListIndex($this->collectionName, 'myfield', true);

        //Check that the index is created
        $info = $this->client->getIndexInfo($this->collectionName, $id);
        $this->assertInternalType('array', $info, "The index info should be an array");
        $this->assertEquals("skiplist", $info['type'], 'The type of the created index should be "skiplist"');
        $this->assertEquals('myfield', $info['fields'][0], "The field of the created index does not match");
        $this->assertTrue($info['unique'], 'The unique of the created index should be "true"');
    }

    /**
     * @covers Paradox\Client::any
     */
    public function testAny()
    {
        $any = $this->client->any($this->collectionName);

        $this->assertInstanceOf('Paradox\AModel', $any, 'The returned object should be of type Paradox\AModel');

        $this->assertContains($any->get('name'),
                array('Horacio Manuel Cartes Jara', 'Tsegaye Kebede', 'Giorgio Napolitano', 'Priscah Jeptoo'),
                "The document retrieved does not seem to be from the collection");
    }

    /**
     * @covers Paradox\Client::findNear
     */
    public function testFindNear()
    {
        $result = $this->client->findNear($this->collectionName, 48, 48, "doc.name in [@horacio]", array('horacio' => 'Horacio Manuel Cartes Jara'), 2);
        $this->assertInternalType('array', $result, "The result set should be an array");

        $resultItem = reset($result);
        $this->assertInstanceOf('Paradox\AModel', $resultItem, 'The result item should be of the type Paradox\AModel');
        $this->assertEquals('Horacio Manuel Cartes Jara', $resultItem->get('name'), 'The name of the found document should be "Horacio Manuel Cartes Jara"');
        $this->assertInternalType('float', $resultItem->getDistance());

        $coordinates = $resultItem->getReferenceCoordinates();
        $this->assertEquals('48', $coordinates['latitude']);
        $this->assertEquals('48', $coordinates['longitude']);
    }

    /**
     * @covers Paradox\Client::findAllNear
     */
    public function testFindAllNear()
    {
        $result = $this->client->findAllNear($this->collectionName, 48, 48, "FILTER doc.name in [@horacio]", array('horacio' => 'Horacio Manuel Cartes Jara'), 2);
        $this->assertInternalType('array', $result, "The result set should be an array");

        $resultItem = reset($result);
        $this->assertInstanceOf('Paradox\AModel', $resultItem, 'The result item should be of the type Paradox\AModel');
        $this->assertEquals('Horacio Manuel Cartes Jara', $resultItem->get('name'), 'The name of the found document should be "Horacio Manuel Cartes Jara"');
        $this->assertInternalType('float', $resultItem->getDistance());

        $coordinates = $resultItem->getReferenceCoordinates();
        $this->assertEquals('48', $coordinates['latitude']);
        $this->assertEquals('48', $coordinates['longitude']);
    }

    /**
     * @covers Paradox\Client::findOneNear
     */
    public function testFindOneNear()
    {
        $result = $this->client->findOneNear($this->collectionName, 48, 48, "doc.name in [@horacio]", array('horacio' => 'Horacio Manuel Cartes Jara'));

        $this->assertInstanceOf('Paradox\AModel', $result, 'The result item should be of the type Paradox\AModel');
        $this->assertEquals('Horacio Manuel Cartes Jara', $result->get('name'), 'The name of the found document should be "Horacio Manuel Cartes Jara"');
        $this->assertInternalType('float', $result->getDistance());

        $coordinates = $result->getReferenceCoordinates();
        $this->assertEquals('48', $coordinates['latitude']);
        $this->assertEquals('48', $coordinates['longitude']);
    }

    /**
     * @covers Paradox\Client::findWithin
     */
    public function testFindWithin()
    {
        $result = $this->client->findWithin($this->collectionName, 48, 48, 1000000, "doc.name in [@horacio]", array('horacio' => 'Horacio Manuel Cartes Jara'));
        $this->assertInternalType('array', $result, "The result set should be an array");

        $resultItem = reset($result);
        $this->assertInstanceOf('Paradox\AModel', $resultItem, 'The result item should be of the type Paradox\AModel');
        $this->assertEquals('Horacio Manuel Cartes Jara', $resultItem->get('name'), 'The name of the found document should be "Horacio Manuel Cartes Jara"');
        $this->assertInternalType('float', $resultItem->getDistance());

        $coordinates = $resultItem->getReferenceCoordinates();
        $this->assertEquals('48', $coordinates['latitude']);
        $this->assertEquals('48', $coordinates['longitude']);
    }

    /**
     * @covers Paradox\Client::findAllWithin
     */
    public function testFindAllWithin()
    {
        $result = $this->client->findAllWithin($this->collectionName, 48, 48, 1000000, "FILTER doc.name in [@horacio]", array('horacio' => 'Horacio Manuel Cartes Jara'));
        $this->assertInternalType('array', $result, "The result set should be an array");

        $resultItem = reset($result);
        $this->assertInstanceOf('Paradox\AModel', $resultItem, 'The result item should be of the type Paradox\AModel');
        $this->assertEquals('Horacio Manuel Cartes Jara', $resultItem->get('name'), 'The name of the found document should be "Horacio Manuel Cartes Jara"');
        $this->assertInternalType('float', $resultItem->getDistance());

        $coordinates = $resultItem->getReferenceCoordinates();
        $this->assertEquals('48', $coordinates['latitude']);
        $this->assertEquals('48', $coordinates['longitude']);
    }

    /**
     * @covers Paradox\Client::findOneWithin
     */
    public function testFindOneWithin()
    {
        $result = $this->client->findOneWithin($this->collectionName, 48, 48, 1000000, "doc.name in [@horacio]", array('horacio' => 'Horacio Manuel Cartes Jara'));

        $this->assertInstanceOf('Paradox\AModel', $result, 'The result item should be of the type Paradox\AModel');
        $this->assertEquals('Horacio Manuel Cartes Jara', $result->get('name'), 'The name of the found document should be "Horacio Manuel Cartes Jara"');
        $this->assertInternalType('float', $result->getDistance());

        $coordinates = $result->getReferenceCoordinates();
        $this->assertEquals('48', $coordinates['latitude']);
        $this->assertEquals('48', $coordinates['longitude']);
    }

    /**
     * @covers Paradox\Client::search
     */
    public function testSearch()
    {
        $result = $this->client->search($this->collectionName, "bio", "marathon", "doc.name in [@tsegaye]", array('tsegaye' => 'Tsegaye Kebede'));

        $this->assertInternalType('array', $result, "The result set should be an array");

        $resultItem = reset($result);
        $this->assertInstanceOf('Paradox\AModel', $resultItem, 'The result item should be of the type Paradox\AModel');
        $this->assertEquals('Tsegaye Kebede', $resultItem->get('name'), 'The name of the found document should be "Tsegaye Kebede"');
    }

    /**
     * @covers Paradox\Client::searchAll
     */
    public function testSearchAll()
    {
        $result = $this->client->searchAll($this->collectionName, "bio", "marathon", "FILTER doc.name in [@tsegaye]", array('tsegaye' => 'Tsegaye Kebede'));

        $this->assertInternalType('array', $result, "The result set should be an array");

        $resultItem = reset($result);
        $this->assertInstanceOf('Paradox\AModel', $resultItem, 'The result item should be of the type Paradox\AModel');
        $this->assertEquals('Tsegaye Kebede', $resultItem->get('name'), 'The name of the found document should be "Tsegaye Kebede"');
    }

    /**
     * @covers Paradox\Client::searchForOne
     */
    public function testSearchForOne()
    {
        $result = $this->client->searchForOne($this->collectionName, "bio", "marathon", "doc.name in [@tsegaye]", array('tsegaye' => 'Tsegaye Kebede'));

        $this->assertInstanceOf('Paradox\AModel', $result, 'The result item should be of the type Paradox\AModel');
        $this->assertEquals('Tsegaye Kebede', $result->get('name'), 'The name of the found document should be "Tsegaye Kebede"');
    }

    /**
     * @covers Paradox\Client::createUser
     * @covers Paradox\Client::deleteUser
     * @covers Paradox\Client::getUserInfo
     */
    public function testCreateUser()
    {
        $result = $this->client->createUser('testuser', 'password', true, array('name' => 'david'));

        $user = $this->client->getUserInfo('testuser');

        $this->assertEquals('testuser', $user['username'], 'The username does not match');
        $this->assertTrue($user['active'], 'The user is not marked as active');
        $this->assertEquals('david', $user['data']['name'], 'The name does not match');

        //Delete the user and verify
        $this->client->deleteUser('testuser');

        try {
            $user = $this->client->getUserInfo('testuser');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to get information about a non-existing user, but an exception was not thrown');
    }

    /**
     * @covers Paradox\Client::changePassword
     */
    public function testChangePassword()
    {
        $result = $this->client->createUser('testuser', 'password', true, array('name' => 'david'));

        $user = $this->client->changePassword('testuser', 'password2');

        //TODO: There isn't a way to assert the password has been changed (https://github.com/triAGENS/ArangoDB/issues/498)
        //Add assertions when it is possible

        //Delete the user
        $this->client->deleteUser('testuser');
    }

    /**
     * @covers Paradox\Client::setUserActive
     */
    public function testSetUserActive()
    {
        //Create inactive user
        $result = $this->client->createUser('testuser', 'password', false, array('name' => 'david'));

        $user = $this->client->getUserInfo('testuser');
        $this->assertFalse($user['active'], 'The user is marked as active');

        //Set to active
        $this->client->setUserActive('testuser', true);

        $user = $this->client->getUserInfo('testuser');
        $this->assertTrue($user['active'], 'The user is not marked as active');

        //Delete the user
        $this->client->deleteUser('testuser');
    }

    /**
     * @covers Paradox\Client::updateUserData
     */
    public function testUpdateUserData()
    {
        $result = $this->client->createUser('testuser', 'password', true, array('name' => 'david'));

        //Update
        $user = $this->client->updateUserData('testuser', array('age' => 20));

        //Verify
        $user = $this->client->getUserInfo('testuser');
        $this->assertEquals(20, $user['data']['age'], 'The age does not match');

        //Delete the user
        $this->client->deleteUser('testuser');
    }

    /**
     * @covers Paradox\Client::getVersion
     */
    public function testGetVersion()
    {
        $version = $this->client->getVersion();

        $this->assertInternalType('string', $version, "The version should be a string");
    }

    /**
     * @covers Paradox\Client::getServerInfo
     */
    public function testGetServerInfo()
    {
        $version = $this->client->getServerInfo();

        $this->assertInternalType('array', $version, "The server info should be an array");
    }

    /**
     * @covers Paradox\Client::getTime
     */
    public function testGetTime()
    {
        $time = $this->client->getTime();

        $this->assertInternalType('float', $time, "The time should be a float");
    }


    /**
     * @covers Paradox\Client::begin
     */
    public function testBegin()
    {
        $this->client->begin();

        $this->assertTrue($this->client->getToolbox()->getTransactionManager()->hasTransaction(), "The transaction was not started");
    }

    /**
     * @covers Paradox\Client::commit
     */
    public function testCommit()
    {
        $this->client->begin();

        $document = $this->client->dispense($this->collectionName);
        $document->set('name', 'john smith');
        $this->client->store($document);
        $this->client->registerResult('store1');

        $document2 = $this->client->dispense($this->collectionName);
        $document2->set('name', 'david smith');
        $this->client->store($document2);
        $this->client->registerResult('store2');

        $document2->set('age', 20);
        $this->client->store($document2);
        $this->client->registerResult('store3');

        $this->client->delete($document2);
        $this->client->registerResult('delete');

        $result = $this->client->commit();

        $this->assertNotNull($result['store1'], "The id after storing a document should not be null");
        $this->assertNotNull($result['store2'], "The id after storing a document should not be null");
        $this->assertNotNull($result['store3'], "The id after storing a document should not be null");
        $this->assertEquals($result['store2'], $result['store3'], "The 2 ids after storing the same document does not match");
        $this->assertTrue($result['delete'], "Deleting a document should return true");
    }

    /**
     * @covers Paradox\Client::cancel
     */
    public function testCancel()
    {
        $this->client->begin();

        $document = $this->client->dispense($this->collectionName);
        $document->set('name', 'john smith');
        $this->client->store($document);

        $this->client->cancel();

        $this->assertFalse($this->client->getToolbox()->getTransactionManager()->hasTransaction(), "The transaction was not cancelled");
    }

    /**
     * @covers Paradox\Client::addReadCollection
     */
    public function testAddReadCollection()
    {
        $this->client->begin();
        $this->client->addReadCollection($this->collectionName);
    }

    /**
     * @covers Paradox\Client::addWriteCollection
     */
    public function testAddWriteCollection()
    {
        $this->client->begin();
        $this->client->addWriteCollection($this->collectionName);
    }

    /**
     * @covers Paradox\Client::registerResult
     */
    public function testRegisterResult()
    {
        $this->client->begin();
        $document = $this->client->dispense($this->collectionName);
        $document->set('name', 'john smith');
        $this->client->store($document);
        $this->client->registerResult('store');
        $result = $this->client->commit();

        $this->assertNotNull($result['store'], "Store should return a valid id");
    }

    /**
     * @covers Paradox\Client::pause
     */
    public function testPause()
    {
        $this->client->begin();
        $document = $this->client->dispense($this->collectionName);
        $document->set('name', 'john smith');
        $id1 = $this->client->store($document);

        $this->assertNull($id1, "Id should be null, since methods can't return things in a transaction");

        $this->client->pause();

        $document2 = $this->client->dispense($this->collectionName);
        $document2->set('name', 'john smith');
        $id2 = $this->client->store($document2);

        $this->assertNotNull($id2, "Id should not be null, since it is outside a transaction");
    }

    /**
     * @covers Paradox\Client::resume
     */
    public function testResume()
    {
        $this->client->begin();
        $document = $this->client->dispense($this->collectionName);
        $document->set('name', 'john smith');
        $id1 = $this->client->store($document);

        $this->assertNull($id1, "Id should be null, since methods can't return things in a transaction");

        $this->client->pause();

        $document2 = $this->client->dispense($this->collectionName);
        $document2->set('name', 'john smith');
        $id2 = $this->client->store($document2);

        $this->assertNotNull($id2, "Id should not be null, since it is outside a transaction");

        $this->client->resume();

        $document3 = $this->client->dispense($this->collectionName);
        $document3->set('name', 'john smith');
        $id3 = $this->client->store($document3);

        $this->assertNull($id3, "Id should be null, since methods can't return things in a transaction");
    }

    /**
     * @covers Paradox\Client::executeTransaction
     */
    public function testExecuteTransaction()
    {
        $action = "function(){ return 'hello'; }";

        $result = $this->client->executeTransaction($action);

        $this->assertEquals('hello', $result, "The result does not match");
    }
    
    /**
     * @covers Paradox\Client::transactionStarted
     */
    public function testTransactionStarted()
    {
    	$this->assertFalse($this->client->transactionStarted(), "There should be no active transaction");
    	
    	$this->client->begin();
    	
    	$this->assertTrue($this->client->transactionStarted(), "There should be an active transaction");
    }

    /**
     * @covers Paradox\Client::createAQLFunction
     */
    public function testCreateAQLFunction()
    {
        $action = "function(){ return 'hello'; }";

        $result = $this->client->createAQLFunction("paradoxtest:helloworld", $action);

        $registered = $this->client->listAQLFunctions("paradoxtest");

        $this->assertCount(1, $registered, "There should only be one paradoxtest function");
        $this->assertArrayHasKey("paradoxtest:helloworld", $registered, "The AQL function was not registered");
        $this->assertEquals($action, $registered['paradoxtest:helloworld'], "The AQL function's code does not match");

        $this->client->deleteAQLFunction("paradoxtest:helloworld");
    }

    /**
     * @covers Paradox\Client::deleteAQLFunction
     */
    public function testDeleteAQLFunction()
    {
        $action = "function(){ return 'hello'; }";

        $result = $this->client->createAQLFunction("paradoxtest:helloworld", $action);

        $registered = $this->client->listAQLFunctions("paradoxtest");

        $this->assertCount(1, $registered, "There should only be one paradoxtest function");
        $this->assertArrayHasKey("paradoxtest:helloworld", $registered, "The AQL function was not registered");

        $this->client->deleteAQLFunction("paradoxtest:helloworld");

        $registered = $this->client->listAQLFunctions("paradoxtest");

        $this->assertEmpty($registered, "The AQL function was not deleted");
    }

    /**
     * @covers Paradox\Client::deleteAQLFunctionsByNamespace
     */
    public function testDeleteAQLFunctionsByNamespace()
    {
        $function = "function(){return 'hello';}";

        $this->client->createAQLFunction("paradoxtest:helloworld", $function);
        $this->client->createAQLFunction("paradoxtest:helloworld2", $function);

        $registered = $this->client->listAQLFunctions("paradoxtest");

        $this->assertCount(2, $registered, "There should be 2 paradoxtest functions");
        $this->assertArrayHasKey("paradoxtest:helloworld", $registered, "The AQL function was not registered");
        $this->assertArrayHasKey("paradoxtest:helloworld2", $registered, "The AQL function was not registered");

        $this->client->deleteAQLFunctionsByNamespace("paradoxtest");

        $registered = $this->client->listAQLFunctions("paradoxtest");

        $this->assertEmpty($registered, "There should be no paradoxtest functions registered");
    }

    /**
     * @covers Paradox\Client::listAQLFunctions
     */
    public function testListAQLFunctions()
    {
        $action = "function(){ return 'hello'; }";

        $result = $this->client->createAQLFunction("paradoxtest1:helloworld", $action);
        $result = $this->client->createAQLFunction("paradoxtest2:helloworld", $action);

        $registered = $this->client->listAQLFunctions("paradoxtest1");

        $this->assertCount(1, $registered, "There should only be one paradoxtest function");
        $this->assertArrayHasKey("paradoxtest1:helloworld", $registered, "The AQL function was not registered");

        $registered = $this->client->listAQLFunctions("paradoxtest2");

        $this->assertCount(1, $registered, "There should only be one paradoxtest function");
        $this->assertArrayHasKey("paradoxtest2:helloworld", $registered, "The AQL function was not registered");

        $this->client->deleteAQLFunction("paradoxtest1:helloworld");
        $this->client->deleteAQLFunction("paradoxtest2:helloworld");

        $registered = $this->client->listAQLFunctions("paradoxtest1");

        $this->assertEmpty($registered, "The AQL function was not deleted");

        $registered = $this->client->listAQLFunctions("paradoxtest2");

        $this->assertEmpty($registered, "The AQL function was not deleted");
    }

    /**
     * @covers Paradox\Client::debug
     */
    public function testDebug()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\Client');

        //Then we need to get the property we wish to test
        //and make it accessible
        $debugger = $reflectionClass->getProperty('_debug');
        $debugger->setAccessible(true);

        $debugReflectionClass = new \ReflectionClass('Paradox\Debug');

        $debugValue = $debugReflectionClass->getProperty('_debug');
        $debugValue->setAccessible(true);

        //Verify debug is initially false
        $debuggerInstance = $debugger->getValue($this->client);
        $this->assertFalse($debugValue->getValue($debuggerInstance), "The debugger value should be false");

        //Set debug to true and verify
        $this->client->debug(true);
        $this->assertTrue($debugValue->getValue($debuggerInstance), "The debugger value should be true");
    }
}
