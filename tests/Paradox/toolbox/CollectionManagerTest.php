<?php
namespace tests\Paradox\toolbox;
use tests\Base;
use Paradox\exceptions\CollectionManagerException;
use Paradox\toolbox\CollectionManager;

/**
 * Tests for the collection manager.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class CollectionManagerTest extends Base
{
    /**
     * The collection name for this test case.
     * @var string
     */
    protected $collectionName = 'CollectionManagerTestCollection';

    /**
     * The graph name for this test case.
     * @var string
     */
    protected $graphName = 'CollectionManagerTestGraph';

    /**
     * Stores the collection manager.
     * @var CollectionManager
     */
    protected $collectionManager;

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

        $this->collectionManager = $this->getCollectionManager();
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
            $client->deleteCollection($this->collectionName . '2');
        } catch (\Exception $e) {
            //Ignore any errors
        }
    }

    /**
     * Convinience function to get the collection manager.
     * @return \Paradox\toolbox\CollectionManager
     */
    protected function getCollectionManager()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword());

        return $client->getToolbox()->getCollectionManager();
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\CollectionManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $property = $reflectionClass->getProperty('_toolbox');
        $property->setAccessible(true);

        //We need to create an empty object to pass to
        //ReflectionProperty's getValue method
        $manager = new CollectionManager($this->getClient()->getToolbox());

        $this->assertInstanceOf('Paradox\Toolbox', $property->getValue($manager), 'CollectionManager constructor did not store a Paradox\Toolbox.');
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createCollection
     */
    public function testCreateCollection()
    {
        $id = $this->collectionManager->createCollection($this->collectionName . '2');

        $this->assertNotNull($id, "Stored collection id should not be null");

        //Try to fetch it from the server
        $info = $this->collectionManager->getCollectionInfo($id);

        $this->assertEquals($this->collectionName . '2', $info['name'], "The retrieved collection name does not match the saved one");

        $this->collectionManager->deleteCollection($this->collectionName . '2');
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createCollection
     */
    public function testCreateInvalidCollection()
    {
        try {
            $id = $this->collectionManager->createCollection('!123456');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail('Creating a collection with an invalid name did not throw an exception');
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::deleteCollection
     */
    public function testDeleteCollection()
    {
        //List the collections
        $collections = $this->collectionManager->listCollections();
        $this->assertContains($this->collectionName, $collections, 'The collection does not exist');

        $this->collectionManager->deleteCollection($this->collectionName);
        $collections = $this->collectionManager->listCollections();
        $this->assertNotContains($this->collectionName, $collections, 'The collection was not deleted successfully');
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::deleteCollection
     */
    public function testDeleteInvalidCollection()
    {
        //Try to delete a nonexistent collection
        try {
            $this->collectionManager->deleteCollection('CollectionThatDoesNotExist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail('Deleting an invalid collection did not throw an exception');
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::renameCollection
     */
    public function testRenameCollection()
    {
        $renamed = $this->collectionName . '_renamed';
        $this->collectionManager->renameCollection($this->collectionName, $renamed);

        $collections = $this->collectionManager->listCollections();
        $this->assertNotContains($this->collectionName, $collections, "Collection was not renamed successfully.");
        $this->assertContains($renamed, $collections, "Collection was not renamed successfully.");

        $this->collectionManager->deleteCollection($renamed);
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::renameCollection
     */
    public function testRenameInvalidCollection()
    {
        try {
            $this->collectionManager->renameCollection('CollectionThatDoesNotExist', 'CollectionThatDoesNotExist_renamed');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\CollectionManagerException');

            return;
        }

         $this->failed('Renaming an invalid collection did not throw an exception');
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::wipe
     */
    public function testWipe()
    {
        $client = $this->getClient();
        $document1 = $client->dispense($this->collectionName);
        $document2 = $client->dispense($this->collectionName);
        $client->store($document1);
        $client->store($document2);

        $this->assertEquals(2, $client->count($this->collectionName), "The number of documents in the collection does not match the number inserted");

        $this->collectionManager->wipe($this->collectionName);
        $this->assertEquals(0, $client->count($this->collectionName), "The collection is not empty after wiping (turncating).");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::wipe
     */
    public function testWipeInvalidCollection()
    {
        try {
            $this->collectionManager->wipe('CollectionThatDoesNotExist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to wipe (truncate) an invalid collection but an exception was not thrown.");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::getCollectionInfo
     */
    public function testGetCollectionInfo()
    {
        $info = $this->collectionManager->getCollectionInfo($this->collectionName);
        $this->assertEquals($this->collectionName, $info['name'], "Collection info's name does not match the name of the collection");
        $this->assertEquals("documents", $info['type'], "Collection's type does not match");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::getCollectionInfo
     */
    public function testGetCollectionInfoForGraph()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $edgesCollection = $client->getToolbox()->getEdgeCollectionName();
        $info = $client->getToolbox()->getCollectionManager()->getCollectionInfo($edgesCollection);
        $this->assertEquals($edgesCollection, $info['name'], "Collection info's name does not match the name of the collection");
        $this->assertEquals("edges", $info['type'], "Collection's type does not match");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::getCollectionInfo
     */
    public function testGetCollectionInfoForInvalidCollection()
    {
        try {
            $info = $this->collectionManager->getCollectionInfo('CollectionThatDoesNotExist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to get information for an invalid collection but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::getCollectionStatistics
     */
    public function testGetCollectionStatistics()
    {
        $statistics = $this->collectionManager->getCollectionStatistics($this->collectionName);
        $this->assertInternalType('array', $statistics, "Collection statistics is not an array");
        $this->assertNotEmpty($statistics, "collection statistics is empty");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::getCollectionStatistics
     */
    public function testGetCollectionStatisticsForInvalidCollection()
    {
        try {
            $info = $this->collectionManager->getCollectionStatistics('CollectionThatDoesNotExist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to get statistics for an invalid collection but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::count
     */
    public function testCount()
    {
       $client = $this->getClient();
       $count = $client->getToolbox()->getCollectionManager()->count($this->collectionName);
       $this->assertEquals(0, $count, 'The number of documents in the collection is not 0');

       $document = $client->dispense($this->collectionName);
       $client->store($document);

       $count = $client->getToolbox()->getCollectionManager()->count($this->collectionName);
       $this->assertEquals(1, $count, 'The number of documents in the collection is not 1');
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::count
     */
    public function testCountForInvalidCollection()
    {
        try {
            $info = $this->collectionManager->count('CollectionThatDoesNotExist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to get the number of documents for an invalid collection but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::listCollections
     */
    public function testListCollections()
    {
        //List user collections in standard form
        $collections = $this->collectionManager->listCollections();
        $this->assertInternalType('array', $collections, "Listed collections is not an array");
        $this->assertContains($this->collectionName, $collections, "$this->collectionName does not exist in the collections list");

        foreach ($collections as $collection) {
            $this->assertInternalType('string', $collection, "Collection name is not a string");
        }

        //List collections including system collections in verbose form
        $collections = $this->collectionManager->listCollections(false, true);
        $this->assertInternalType('array', $collections, "Listed collections is not an array");
        $this->assertArrayHasKey($this->collectionName, $collections, "$this->collectionName does not exist in the collections list");
        $this->assertArrayHasKey('_users', $collections, "The _users array does not exist in the collections list which includes system collections");

        foreach ($collections as $collection => $data) {
            $this->assertInternalType('array', $data, "Collection data is not an array");
            $this->assertEquals($collection, $data['name'], "The collection name in the data array does not match the key of that element");
        }

    }

    /**
     * @covers Paradox\toolbox\CollectionManager::listCollections
     */
    public function testListCollectionsInvalidServer()
    {
        $client = $this->getClient('tcp://nonexistenthost:8529', $this->getDefaultUsername(), $this->getDefaultPassword());

        try {
            $collections = $client->listCollections();

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to get the collections of a nonexistent server but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::loadCollection
     */
    public function testLoadCollection()
    {
       $this->collectionManager->unloadCollection($this->collectionName);

       //Verify it is unloaded. Note that we list collections, because using getCollectionInfo() loads the collection.
       $collectionInfo = $this->collectionManager->listCollections(true, true);

       //Status 2 = unloaded
       $this->assertEquals(2, $collectionInfo[$this->collectionName]['status'], "The collection was not unloaded");

       //Load the collection
       $this->collectionManager->loadCollection($this->collectionName);

       //Verify it is loaded. Note that we list collections, because using getCollectionInfo() loads the collection.
       $collectionInfo = $this->collectionManager->listCollections(true, true);

       //Status 3 = loaded
       $this->assertEquals(3, $collectionInfo[$this->collectionName]['status'], "The collection was not loaded");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::loadCollection
     */
    public function testLoadInvalidCollection()
    {
        try {
            $collections = $this->collectionManager->loadCollection('CollectionThatDoesNotExist');

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to load a non-existent collection but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::unloadCollection
     */
    public function testUnloadCollection()
    {
        $this->collectionManager->loadCollection($this->collectionName);

        //Verify it is loaded. Note that we list collections, because using getCollectionInfo() loads the collection.
        $collectionInfo = $this->collectionManager->listCollections(true, true);

        //Status 3 = loaded
        $this->assertEquals(3, $collectionInfo[$this->collectionName]['status'], "The collection was not loaded");

        //Unload the collection
        $this->collectionManager->unloadCollection($this->collectionName);

        //Verify it is unloaded. Note that we list collections, because using getCollectionInfo() loads the collection.
        $collectionInfo = $this->collectionManager->listCollections(true, true);

        //Status 2 = unloaded or 4 = being unloaded
        $this->assertContains($collectionInfo[$this->collectionName]['status'], array(2, 4), "The collection was not unloaded");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::unloadCollection
     */
    public function testUnloadInvalidCollection()
    {
        try {
            $collections = $this->collectionManager->unloadCollection('CollectionThatDoesNotExist');

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to unload a non-existent collection but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createCapConstraint
     */
    public function testCreateCapConstraint()
    {
        $id = $this->collectionManager->createCapConstraint($this->collectionName, 10);

        $this->assertNotNull($id, "Created index should have a valid id");

        $indexInfo = $this->collectionManager->getIndexInfo($this->collectionName, $id);

        $this->assertEquals("cap", $indexInfo['type'], 'The type of the created index is not "cap"');
        $this->assertEquals(10, $indexInfo['size'], "The size of the cap constraint is not 10");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createCapConstraint
     */
    public function testCreateCapConstraintOnInvalidCollection()
    {
        try {
            $id = $this->collectionManager->createCapConstraint('CollectionThatDoesNotExist', 10);

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to create a cap constraint on a non-existent collection but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createGeoIndex
     */
    public function testCreateGeo1Index()
    {
        $id = $this->collectionManager->createGeoIndex($this->collectionName, "geofield", true, true, true);

        $this->assertNotNull($id, "Created index should have a valid id");

        $indexInfo = $this->collectionManager->getIndexInfo($this->collectionName, $id);

        $this->assertEquals("geo1", $indexInfo['type'], 'The type of the created index is not "geo1"');
        $this->assertCount(1, $indexInfo['fields'], "The index does not contain only 1 field");
        $this->assertEquals("geofield", $indexInfo['fields'][0], 'The indexed field is not "geofield"');
        $this->assertTrue($indexInfo['geoJson'], "geoJson is not enabled");
        $this->assertTrue($indexInfo['constraint'], "The index was not created as a constraint");
        $this->assertTrue($indexInfo['ignoreNull'], "ignoreNull is not enabled");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createGeoIndex
     */
    public function testCreateGeo2Index()
    {
        $id = $this->collectionManager->createGeoIndex($this->collectionName, array('lat', 'long'));

        $this->assertNotNull($id, "Created index should have a valid id");

        $indexInfo = $this->collectionManager->getIndexInfo($this->collectionName, $id);

        $this->assertEquals("geo2", $indexInfo['type'], 'The type of the created index is not "geo2"');
        $this->assertCount(2, $indexInfo['fields'], "The index does not contain only 2 fields");
        $this->assertEquals("lat", $indexInfo['fields'][0], 'The first indexed field is not "lat"');
        $this->assertEquals("long", $indexInfo['fields'][1], 'The first indexed field is not "long"');
        $this->assertArrayNotHasKey('geoJson', $indexInfo, "geoJson is enabled");
        $this->assertFalse($indexInfo['constraint'], "The index was created as a constraint");
        $this->assertFalse($indexInfo['ignoreNull'], "ignoreNull is enabled");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createGeoIndex
     */
    public function testCreateGeoIndexOnInvalidCollection()
    {
        try {
            $id = $this->collectionManager->createGeoIndex('CollectionThatDoesNotExist', 'geofield');

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to create a geo index on a non-existent collection but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createHashIndex
     */
    public function testCreateHashIndexWithTwoFields()
    {
        $id = $this->collectionManager->createHashIndex($this->collectionName, array('field1', 'field2'), true);

        $this->assertNotNull($id, "Created index should have a valid id");

        $indexInfo = $this->collectionManager->getIndexInfo($this->collectionName, $id);

        $this->assertEquals("hash", $indexInfo['type'], 'The type of the created index is not "hash"');
        $this->assertCount(2, $indexInfo['fields'], "The index does not contain only 2 fields");
        $this->assertEquals("field1", $indexInfo['fields'][0], 'The first indexed field is not "field1"');
        $this->assertEquals("field2", $indexInfo['fields'][1], 'The first indexed field is not "field2"');
        $this->assertTrue($indexInfo['unique'], "The index was not created as unique index");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createHashIndex
     */
    public function testCreateHashIndexWithOneFields()
    {
        $id = $this->collectionManager->createHashIndex($this->collectionName, 'field1');

        $this->assertNotNull($id, "Created index should have a valid id");

        $indexInfo = $this->collectionManager->getIndexInfo($this->collectionName, $id);

        $this->assertEquals("hash", $indexInfo['type'], 'The type of the created index is not "hash"');
        $this->assertCount(1, $indexInfo['fields'], "The index does not contain only 1 field");
        $this->assertEquals("field1", $indexInfo['fields'][0], 'The first indexed field is not "field1"');
        $this->assertFalse($indexInfo['unique'], "The index was created as unique index");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createHashIndex
     */
    public function testCreateHashIndexOnInvalidCollection()
    {
        try {
            $id = $this->collectionManager->createHashIndex('CollectionThatDoesNotExist', 'field1');

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to create a hash index on a non-existent collection but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createFulltextIndex
     */
    public function testCreateFulltextIndex()
    {
        $id = $this->collectionManager->createFulltextIndex($this->collectionName, 'message', 20);

        $this->assertNotNull($id, "Created index should have a valid id");

        $indexInfo = $this->collectionManager->getIndexInfo($this->collectionName, $id);

        $this->assertEquals("fulltext", $indexInfo['type'], 'The type of the created index is not "fulltext"');
        $this->assertCount(1, $indexInfo['fields'], "The index does not contain only 1 field");
        $this->assertEquals("message", $indexInfo['fields'][0], 'The first indexed field is not "message"');
        $this->assertEquals(20, $indexInfo['minLength'], "The minLength on the index is not 20");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createFulltextIndex
     */
    public function testCreateFulltextIndexWithMoreThanOneField()
    {
        try {
            $id = $this->collectionManager->createFulltextIndex($this->collectionName, array('title', 'message'), 20);

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to create a fulltext index with 2 fields but an exception was not thrown");

    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createFulltextIndex
     */
    public function testCreateFulltextIndexOnInvalidCollection()
    {
        try {
            $id = $this->collectionManager->createFulltextIndex('CollectionThatDoesNotExist', 'message');

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to create a fulltext index on a collection that does not exist but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createSkipListIndex
     */
    public function testCreateSkipListIndexWithOneField()
    {
        $id = $this->collectionManager->createSkipListIndex($this->collectionName, 'field1', true);

        $this->assertNotNull($id, "Created index should have a valid id");

        $indexInfo = $this->collectionManager->getIndexInfo($this->collectionName, $id);

        $this->assertEquals("skiplist", $indexInfo['type'], 'The type of the created index is not "skiplist"');
        $this->assertCount(1, $indexInfo['fields'], "The index does not contain only 1 field");
        $this->assertEquals("field1", $indexInfo['fields'][0], 'The first indexed field is not "field1"');
        $this->assertTrue($indexInfo['unique'], "The created index is not unique");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createSkipListIndex
     */
    public function testCreateSkipListIndexWithTwoFields()
    {
        $id = $this->collectionManager->createSkipListIndex($this->collectionName, array('field1', 'field2'), false);

        $this->assertNotNull($id, "Created index should have a valid id");

        $indexInfo = $this->collectionManager->getIndexInfo($this->collectionName, $id);

        $this->assertEquals("skiplist", $indexInfo['type'], 'The type of the created index is not "skiplist"');
        $this->assertCount(2, $indexInfo['fields'], "The index does not contain only 2 fields");
        $this->assertEquals("field1", $indexInfo['fields'][0], 'The first indexed field is not "field1"');
        $this->assertEquals("field2", $indexInfo['fields'][1], 'The first indexed field is not "field2"');
        $this->assertFalse($indexInfo['unique'], "The created index is unique");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::createSkipListIndex
     */
    public function testCreateSkipListIndexOnInvalidCollection()
    {
        try {
            $id = $this->collectionManager->createSkipListIndex('CollectionThatDoesNotExist', 'field1');

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to create a skiplist index on a collection that does not exist but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::deleteIndex
     */
    public function testDeleteIndex()
    {
        $id = $this->collectionManager->createSkipListIndex($this->collectionName, 'field1');

        $indices = $this->collectionManager->listIndices($this->collectionName);
        $this->assertContains($this->collectionName . '/' . $id, $indices);

        $this->collectionManager->deleteIndex($this->collectionName, $id);

        $indices = $this->collectionManager->listIndices($this->collectionName);
        $this->assertNotContains($this->collectionName . '/' . $id, $indices);
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::deleteIndex
     */
    public function testDeleteIndexFromNonExistentCollection()
    {
        try {
            $this->collectionManager->deleteIndex($this->collectionName, 'IndexThatDoesNotExist');

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to delete an index that does not exist on a collection but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::getIndexInfo
     */
    public function testGetIndexInfo()
    {
        $id = $this->collectionManager->createSkipListIndex($this->collectionName, 'field1');

        $indexInfo = $this->collectionManager->getIndexInfo($this->collectionName, $id);

        $this->assertEquals("skiplist", $indexInfo['type'], 'The type of the created index is not "skiplist"');
        $this->assertCount(1, $indexInfo['fields'], "The index does not contain only 1 fields");
        $this->assertEquals("field1", $indexInfo['fields'][0], 'The first indexed field is not "field1"');
        $this->assertFalse($indexInfo['unique'], "The created index is unique");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::getIndexInfo
     */
    public function testGetIndexInfoFromNonExistingIndex()
    {
        $indexInfo = $this->collectionManager->getIndexInfo($this->collectionName, 'IndexThatDoesNotExist');

        $this->assertNull($indexInfo, "Getting index info for non-existing index returned something");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::listIndices
     */
    public function testListIndicesFromCacheWithIndexInfo()
    {
        $indices = $this->collectionManager->listIndices($this->collectionName, true);

        $cached = $this->collectionManager->listIndices($this->collectionName, true);

        $this->assertInternalType('array', $indices, "The returned indices list is not an array");

        foreach ($indices as $index) {
            $this->assertInternalType('array', $index, "The returned index info for each index is not an array");
        }

        $this->assertEquals($indices, $cached, "The indices list and cached version does not match");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::listIndices
     */
    public function testListIndicesWithoutIndexInfo()
    {
        $indices = $this->collectionManager->listIndices($this->collectionName, false);

        $this->assertInternalType('array', $indices, "The returned indices list is not an array");

        foreach ($indices as $index) {
            $this->assertInternalType('string', $index, "The returned index ids for each index is not a string");
        }
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::listIndices
     */
    public function testListIndicesOnInvalidCollection()
    {

        try {
            $indices = $this->collectionManager->listIndices('CollectionThatDoesNotExist', false);

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\CollectionManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\CollectionManagerException');

            return;
        }

        $this->fail("Tried to list the indices on a collection that does not exist but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::getGeoFieldsForAQL
     */
    public function testGetGeoFieldsForAQLOnCollectionWithOneGeoField()
    {
        $id = $this->collectionManager->createGeoIndex($this->collectionName, 'geofield');
        $fields = $this->collectionManager->getGeoFieldsForAQL($this->collectionName);

        $this->assertInternalType('array', $fields, "The geo fields returned is not an array");
        $this->assertCount(1, $fields, "The number of geo fields found is not 1");
        $this->assertEquals('geofield', $fields[0], 'The geo field returned is no "geofield"');
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::getGeoFieldsForAQL
     */
    public function testGetGeoFieldsForAQLOnCollectionWithTwoGeoFields()
    {
        $id = $this->collectionManager->createGeoIndex($this->collectionName, array('lat', 'long'));
        $fields = $this->collectionManager->getGeoFieldsForAQL($this->collectionName);

        $this->assertInternalType('array', $fields, "The geo fields returned is not an array");
        $this->assertCount(2, $fields, "The number of geo fields found is not 2");
        $this->assertEquals('lat', $fields[0], 'The geo field returned is no "lat"');
        $this->assertEquals('long', $fields[1], 'The geo field returned is no "long"');
    }

    /**
     * @covers Paradox\toolbox\CollectionManager::getGeoFieldsForAQL
     */
    public function testGetGeoFieldsForAQLOnCollectionWithoutGeoIndex()
    {
        $fields = $this->collectionManager->getGeoFieldsForAQL($this->collectionName);
        $this->assertNull($fields, "Geo fields were returned for a collection that does not have a geo index");
    }
}
