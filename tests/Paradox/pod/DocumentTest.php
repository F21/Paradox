<?php
namespace tests\Paradox\pod;
use tests\Base;
use Paradox\pod\Document;
use Paradox\Event;

/**
 * Tests for the document pod.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class DocumentTest extends Base
{
    /**
     * Stores an instance of the document pod.
     * @var Document
     */
    protected $document;

    /**
     * The collection name for this test case.
     * @var string
     */
    protected $collectionName = 'CollectionManagerTestCollection';

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

        $client->createCollection($this->collectionName);

        $this->document = new Document($client->getToolbox(), $this->collectionName);
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
    }

    /**
     * @covers Paradox\pod\Document::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Document');

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

        $initialData = array('test' => 'test');
        $document = new Document($this->getClient()->getToolbox(), $this->collectionName, $initialData , false);

        $this->assertInstanceOf('Paradox\Toolbox', $toolbox->getValue($document), 'Constructor did not store a Paradox\Toolbox.');
        $this->assertEquals($this->collectionName, $type->getValue($document), "The type of the created document does not match");
        $this->assertEquals($initialData, $data->getValue($document), "The data in the created document does not match");
        $this->assertFalse($new->getValue($document), "The new state of the document is not false");
    }

    /**
     * @covers Paradox\pod\Document::__clone
     */
    public function test__clone()
    {
        $clone = clone $this->document;

        $this->assertNull($clone->getId(), "The id of the cloned document should be null");
        $this->assertNull($clone->getKey(), "The key of the cloned document should be null");
        $this->assertNull($clone->getRevision(), "The revision of the cloned document should be null");
        $this->assertTrue($clone->isNew(), "The cloned document should be marked as new");
        $this->assertTrue($clone->hasChanged(), "The cloned document should be marked as new");
    }

    /**
     * @covers Paradox\pod\Document::set
     */
    public function testSet()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Document');

        //Then we need to get the property we wish to test
        //and make it accessible
        $data = $reflectionClass->getProperty('_data');
        $data->setAccessible(true);

        $this->document->set('mykey', 'myvalue');

        $this->assertArrayHasKey('mykey', $data->getValue($this->document), 'The data array should have a "mykey" key');
        $this->assertEquals('myvalue', $data->getValue($this->document)['mykey'], 'The value of the "mykey" key does not match');

        $this->assertTrue($this->document->hasChanged(), "The document was not marked as changed");
    }

    /**
     * @covers Paradox\pod\Document::set
     */
    public function testSetWithSystemKey()
    {

        try {
            $this->document->set('_id', 'mycollection/123456');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodException', $e, 'Exception thrown was not a Paradox\exceptions\PodException');

            return;
        }

        $this->fail("Setting a system property on a document did not throw an exception");

    }

    /**
     * @covers Paradox\pod\Document::get
     */
    public function testGet()
    {
        $this->document->set('mykey', 'myvalue');

        $value = $this->document->get('mykey');

        $this->assertEquals('myvalue', $value, 'The retrieved value does not match the saved value');
    }

    /**
     * @covers Paradox\pod\Document::get
     */
    public function testGetWithSystemKey()
    {

        try {
            $value = $this->document->get('_id');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodException', $e, 'Exception thrown was not a Paradox\exceptions\PodException');

            return;
        }

        $this->fail("Getting a system property using get() did not throw an exception");
    }

    /**
     * @covers Paradox\pod\Document::setKey
     */
    public function testSetKey()
    {
        try {
            $value = $this->document->setKey('123456');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodException', $e, 'Exception thrown was not a Paradox\exceptions\PodException');

            return;
        }

        $this->fail("Setting the document's key using setKey() did not throw an exception");
    }

    /**
     * @covers Paradox\pod\Document::setRevision
     */
    public function testSetRevision()
    {
       $this->document->setRevision('myrevision');

       $this->assertEquals('myrevision', $this->document->getRevision(), "The revision in the document does not match");
    }

    /**
     * @covers Paradox\pod\Document::setId
     */
    public function testSetId()
    {
        $this->document->setId('mycollection/123456');

        $this->assertEquals('mycollection/123456', $this->document->getId(), "The id in the document does not match");
        $this->assertEquals('123456', $this->document->getKey(), "The key in the document does not match");
    }

    /**
     * @covers Paradox\pod\Document::setId
     */
    public function testSetIdTwice()
    {
        $this->document->setId('mycollection/123456');

        try {
            $this->document->setId('mycollection/1234567');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodException', $e, 'Exception thrown was not a Paradox\exceptions\PodException');

            return;
        }

        $this->fail("Setting the id on a document already with an id did not throw an exception");
    }

    /**
     * @covers Paradox\pod\Document::setId
     */
    public function testSetInvalidId()
    {

        try {
            $this->document->setId('mycollection1234567');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodException', $e, 'Exception thrown was not a Paradox\exceptions\PodException');

            return;
        }

        $this->fail("Setting an invalid id did not throw an exception");
    }

    /**
     * @covers Paradox\pod\Document::getKey
     */
    public function testGetKey()
    {
        $this->document->setId('mycollection/123456');

        $this->assertEquals('123456', $this->document->getKey(), "The retrieved key did not match the stored value");
    }

    /**
     * @covers Paradox\pod\Document::getKey
     */
    public function testGetNullKey()
    {
        $this->assertNull($this->document->getKey(), "The key should be null");
    }

    /**
     * @covers Paradox\pod\Document::getId
     */
    public function testGetId()
    {
        $this->document->setId('mycollection/123456');
        $this->assertEquals('mycollection/123456', $this->document->getId(), "The retrieved id did not match the stored id");
    }

    /**
     * @covers Paradox\pod\Document::getId
     */
    public function testGetNullId()
    {
        $this->assertNull($this->document->getId(), "The retrieved id should be null");
    }

    /**
     * @covers Paradox\pod\Document::getRevision
     */
    public function testGetRevision()
    {
        $this->document->setRevision('123456');
        $this->assertEquals('123456', $this->document->getRevision(), "The retrieved revision does not match");
    }

    /**
     * @covers Paradox\pod\Document::getRevision
     */
    public function testGetNullRevision()
    {
        $this->assertNull($this->document->getRevision(), "The retrieved revision should be null");
    }

    /**
     * @covers Paradox\pod\Document::setDistanceInfo
     */
    public function testSetDistanceInfo()
    {
        $document = new Document($this->getClient()->getToolbox(), $this->collectionName, array('_paradox_distance_parameter' => 10000));

        $document->setDistanceInfo(48.0, 48.0, 'mycollection/123456');

        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Document');

        //Then we need to get the property we wish to test
        //and make it accessible
        $referenceCoordinates = $reflectionClass->getProperty('_referenceCoordinates');
        $referenceCoordinates->setAccessible(true);

        $distance = $reflectionClass->getProperty('_distance');
        $distance->setAccessible(true);

        $referencePodId = $reflectionClass->getProperty('_referencePodId');
        $referencePodId->setAccessible(true);

        $data = $reflectionClass->getProperty('_data');
        $data->setAccessible(true);

        //Check coordinates
        $coordinates = $referenceCoordinates->getValue($document);
        $this->assertCount(2, $coordinates, "The coordinates should only have 2 keys");
        $this->assertArrayHasKey('latitude', $coordinates, 'The coordinates do not have a "latitude" key');
        $this->assertArrayHasKey('longitude', $coordinates, 'The coordinates do not have a "longitude" key');
        $this->assertEquals(48.0, $coordinates['latitude'], "The latitude does not match the setted latitude");
        $this->assertEquals(48.0, $coordinates['longitude'], "The longitude does not match the setted longitude");

        //Check distance
        $this->assertEquals(10000, $distance->getValue($document), "The distance does not match the setted value");
        $this->assertArrayNotHasKey('_paradox_distance_parameter', $data->getValue($document), 'The "_paradox_distance_parameter" was not removed after processing');

        //Check reference pod id
        $this->assertEquals('mycollection/123456', $referencePodId->getValue($document), "The reference pod id does not match the setted value");
    }

    /**
     * @covers Paradox\pod\Document::setDistanceInfo
     */
    public function testSetDistanceInfoTwice()
    {
        $document = new Document($this->getClient()->getToolbox(), $this->collectionName, array('_paradox_distance_parameter' => 10000));

        $document->setDistanceInfo(48.0, 48.0, 'mycollection/123456');

        try {
            $document->setDistanceInfo(48.1, 48.1, 'mycollection/1234567');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodException', $e, 'Exception thrown was not a Paradox\exceptions\PodException');

            return;
        }

        $this->fail("Setting the distance info twice on a pod did not throw an exception");
    }

    /**
     * @covers Paradox\pod\Document::getDistance
     */
    public function testGetDistance()
    {
        $document = new Document($this->getClient()->getToolbox(), $this->collectionName, array('_paradox_distance_parameter' => 10000));

        $document->setDistanceInfo(48.0, 48.0, 'mycollection/123456');

        $this->assertEquals(10000, $document->getDistance(), "The retrieved distance does not match the setted value");
    }

    /**
     * @covers Paradox\pod\Document::getDistance
     */
    public function testGetNullDistance()
    {
        $this->assertNull($this->document->getDistance(), "The retrieved distance should be null");
    }

    /**
     * @covers Paradox\pod\Document::getReferenceCoordinates
     */
    public function testGetReferenceCoordinates()
    {
        $document = new Document($this->getClient()->getToolbox(), $this->collectionName, array('_paradox_distance_parameter' => 10000));

        $document->setDistanceInfo(48.0, 48.0, 'mycollection/123456');

        $coordinates = $document->getReferenceCoordinates();

        $this->assertInternalType('array', $coordinates, "The reference coordinates should be an array");
        $this->assertEquals(48.0, $coordinates['latitude'], "The latitude does not match the setted value");
        $this->assertEquals(48.0, $coordinates['longitude'], "The longitude does not match the setted value");
    }

    /**
     * @covers Paradox\pod\Document::getReferenceCoordinates
     */
    public function testGetNullReferenceCoordinates()
    {
        $this->assertNull($this->document->getReferenceCoordinates(), "The retrieved reference coordinates should be null");
    }

    /**
     * @covers Paradox\pod\Document::getReferencePod
     */
    public function testGetReferencePod()
    {
        $client = $this->getClient();

        $document1 = $client->dispense($this->collectionName);
        $document1->set('name', 'Horacio Manuel Cartes Jara');
        $document1->set('geofield', array(48.1, 48.1));
        $doc1Id = $client->store($document1);

        $document2 = $client->dispense($this->collectionName);
        $document2->set('name', 'Tsegaye Kebede');
        $document2->set('geofield', array(48.1, 48.1));
        $document2->set('_paradox_distance_parameter', 5000);
        $doc2Id = $client->store($document2);

        $document2->getPod()->setDistanceInfo(48.0, 48.0, $this->collectionName . "/$doc1Id");

        $reference = $document2->getReferencePod();
        $this->assertEquals($this->collectionName . '/' . $doc1Id, $reference->getId(), "The reference pod's id does not match the id used as the reference pod");
    }

    /**
     * @covers Paradox\pod\Document::getReferencePod
     */
    public function testGetNullReferencePod()
    {
        $this->assertNull($this->document->getReferencePod(), "The retrieved reference pod should be null");
    }

    /**
     * @covers Paradox\pod\Document::getCoordinates
     */
    public function testGetCoordinatesWithGeo1Index()
    {
        $client = $this->getClient();
        $client->createGeoIndex($this->collectionName, 'geofield');

        $document = $client->dispense($this->collectionName);
        $document->set('name', 'Horacio Manuel Cartes Jara');
        $document->set('geofield', array(48.1, 48.1));
        $docId = $client->store($document);

        $retrieved = $client->load($this->collectionName, $docId);

        $coordinates = $retrieved->getPod()->getCoordinates();

        $this->assertInternalType('array', $coordinates, "The coordinates should be an associative array");
        $this->assertEquals(48.1, $coordinates['latitude'], "The latitude did not match");
        $this->assertEquals(48.1, $coordinates['longitude'], "The longitude did not match");
    }

    /**
     * @covers Paradox\pod\Document::getCoordinates
     */
    public function testGetCoordinatesWithGeo2Index()
    {
        $client = $this->getClient();
        $client->createGeoIndex($this->collectionName, array('lat', 'long'));

        $document = $client->dispense($this->collectionName);
        $document->set('name', 'Horacio Manuel Cartes Jara');
        $document->set('lat', 48.1);
        $document->set('long', 48.1);
        $docId = $client->store($document);

        $retrieved = $client->load($this->collectionName, $docId);

        $coordinates = $retrieved->getPod()->getCoordinates();

        $this->assertInternalType('array', $coordinates, "The coordinates should be an associative array");
        $this->assertEquals(48.1, $coordinates['latitude'], "The latitude did not match");
        $this->assertEquals(48.1, $coordinates['longitude'], "The longitude did not match");
    }

    /**
     * @covers Paradox\pod\Document::getCoordinates
     */
    public function testGetCoordinatesWithNoGeoIndex()
    {
        $this->assertNull($this->document->getCoordinates(), "The document should have not geo fields and therefore, no coordinates");
    }

    /**
     * @covers Paradox\pod\Document::setSaved
     */
    public function testSetSaved()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Document');

        //Then we need to get the property we wish to test
        //and make it accessible
        $new = $reflectionClass->getProperty('_new');
        $new->setAccessible(true);

        $changed = $reflectionClass->getProperty('_changed');
        $changed->setAccessible(true);

        $this->document->setSaved();

        $this->assertFalse($new->getValue($this->document), "The document should not be marked as new");
        $this->assertFalse($changed->getValue($this->document), "The document should not be marked as changed");
    }

    /**
     * @covers Paradox\pod\Document::resetMeta
     */
    public function testResetMeta()
    {
        $this->document->setId('mycollection/123456');
        $this->document->setRevision('myrevision');
        $this->document->set('mykey', 'myvalue');

        $this->document->resetMeta(); //Reset the data

        $this->assertNull($this->document->getId(), "The converted document's id should be null");
        $this->assertNull($this->document->getKey(), "The converted document's key should be null");
        $this->assertNull($this->document->getRevision(), "The converted document's revision should be null");
        $this->assertEquals('myvalue', $this->document->get('mykey'), "The converted document's data does not match");
    }

    /**
     * @covers Paradox\pod\Document::toJSON
     */
    public function testToJSON()
    {
        $this->document->setId('mycollection/123456');
        $this->document->setRevision('myrevision');
        $this->document->set('mykey', 'myvalue');

        $converted = $this->document->toJSON();
        $decoded = json_decode($converted);
        $this->assertEquals('mycollection/123456', $decoded->_id, "The converted document's id does not match");
        $this->assertEquals('myrevision', $decoded->_rev, "The converted document's revision does not match");
        $this->assertEquals('myvalue', $decoded->mykey, "The converted document's data does not match");
    }

    /**
     * @covers Paradox\pod\Document::toTransactionJSON
     */
    public function testToTransactionJSON()
    {
        $this->document->setId('mycollection/123456');
        $this->document->setRevision('myrevision');
        $this->document->set('mykey', 'myvalue');

        $converted = $this->document->toTransactionJSON();
        $decoded = json_decode($converted);
        $this->assertFalse(isset($decoded->_id), "The converted document should not have an id");
        $this->assertFalse(isset($decoded->_key), "The converted document should not have a key");
        $this->assertEquals('myrevision', $decoded->_rev, "The converted document's revision does not match");
        $this->assertEquals('myvalue', $decoded->mykey, "The converted document's data does not match");
    }

    /**
     * @covers Paradox\pod\Document::toDriverDocument
     */
    public function testToDriverDocument()
    {
        $this->document->setId('mycollection/123456');
        $this->document->setRevision('myrevision');
        $this->document->set('mykey', 'myvalue');

        $converted = $this->document->toDriverDocument();
        $this->assertInstanceOf('triagens\ArangoDb\Document', $converted, 'The converted document is not of type \triagens\ArangoDb\triagens\ArangoDb\Document');
        $this->assertEquals('mycollection/123456', $converted->getInternalId(), "The converted document's id does not match");
        $this->assertEquals('myrevision', $converted->getRevision(), "The converted document's revision does not match");
        $this->assertEquals('myvalue', $converted->get('mykey'), "The converted document's data does not match");
    }

    /**
     * @covers Paradox\pod\Document::getType
     */
    public function testGetType()
    {
        $this->assertEquals($this->collectionName, $this->document->getType(), "The type of the document does not match");
    }

    /**
     * @covers Paradox\pod\Document::isGraph
     */
    public function testIsGraph()
    {
        $this->assertFalse($this->document->isGraph(), "This document is not part of a graph, so isGraph() should be false");
    }

    /**
     * @covers Paradox\pod\Document::isNew
     */
    public function testIsNew()
    {
        $this->assertTrue($this->document->isNew(), "This document should be marked as new");
    }

    /**
     * @covers Paradox\pod\Document::hasChanged
     */
    public function testHasChanged()
    {
        $this->assertTrue($this->document->hasChanged(), "This document should be marked as changed");
    }

    /**
     * @covers Paradox\pod\Document::loadFromDriver
     */
    public function testLoadFromDriver()
    {
        $driverDocument = new \triagens\ArangoDb\Document();
        $driverDocument->setInternalId('mycollection/123456');
        $driverDocument->setRevision('myrevision');
        $driverDocument->set('mykey', 'myvalue');

        $this->document->loadFromDriver($driverDocument);

        $this->assertEquals('mycollection/123456', $this->document->getId(), "The id does not match");
        $this->assertEquals('myrevision', $this->document->getRevision(), "The revision does not match");
        $this->assertEquals('myvalue', $this->document->get('mykey'), 'The value for "mykey" does not match');
    }

    /**
     * @covers Paradox\pod\Document::loadFromArray
     */
    public function testLoadFromArray()
    {
        $array = array();
        $array['_id'] = 'mycollection/123456';
        $array['_rev'] = 'myrevision';
        $array['_key'] = '123456';
        $array['mykey'] = 'myvalue';

        $this->document->loadFromArray($array);

        $this->assertEquals('mycollection/123456', $this->document->getId(), "The id does not match");
        $this->assertEquals('myrevision', $this->document->getRevision(), "The revision does not match");
        $this->assertEquals('myvalue', $this->document->get('mykey'), 'The value for "mykey" does not match');
    }

    /**
     * @covers Paradox\pod\Document::loadModel
     */
    public function testLoadModel()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Document');

        //Then we need to get the property we wish to test
        //and make it accessible
        $documentModel = $reflectionClass->getProperty('_model');
        $documentModel->setAccessible(true);

        $model = $this->getMockForAbstractClass('Paradox\AModel');

        $this->document->loadModel($model);

        $this->assertEquals($model, $documentModel->getValue($this->document), "The model loaded into the document does not match");

        //Load a model again and test for exception
        $model2 = $this->getMockForAbstractClass('Paradox\AModel');

        try {
            $this->document->loadModel($model2);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodException', $e, 'Exception thrown was not a Paradox\exceptions\PodException');

            return;
        }

        $this->fail("Setting a model into a pod which already has a model did not throw an exception");
    }

    /**
     * @covers Paradox\pod\Document::near
     */
    public function testNear()
    {
        //Setup
        $client = $this->getClient();
        $client->createGeoIndex($this->collectionName, 'geofield');

        $document1 = $client->dispense($this->collectionName);
        $document1->set('name', 'Horacio Manuel Cartes Jara');
        $document1->set('geofield', array(48.1, 48.1));
        $doc1Id = $client->store($document1);

        $document2 = $client->dispense($this->collectionName);
        $document2->set('name', 'Tsegaye Kebede');
        $document2->set('geofield', array(48.0, 48.0));
        $doc2Id = $client->store($document2);

        $document3 = $client->dispense($this->collectionName);
        $document3->set('name', 'Barack Obama');
        $document3->set('geofield', array(50, 50));
        $doc3Id = $client->store($document3);

        $pod = $document1->getPod();

        $near = $pod->near("FILTER myplaceholder.name IN [@barack, @horacio]", array("barack" => "Barack Obama", "horacio" => "Horacio Manuel Cartes Jara"), 200, "myplaceholder");

        $this->assertInternalType('array', $near, "The result set should be an array");
        $this->assertCount(1, $near, "The result set should only contain 1 result");

        $result = reset($near);
        $this->assertEquals($document3->getPod()->getId(), $result->getId(), "The result's id does not match");
        $this->assertInternalType('float', $result->getDistance(), "The distance should be an integer");

        $referenceCoordinates = $result->getReferenceCoordinates();
        $this->assertEquals($document1->getPod()->getCoordinates(), $referenceCoordinates, "The reference coordinates do no match the reference's");
        $this->assertEquals($document1->getPod()->getId(), $result->getReferencePod()->getPod()->getId(), "The reference pod does not match the pod that ran the near() query");
    }

    /**
     * @covers Paradox\pod\Document::within
     */
    public function testWithin()
    {
        //Setup
        $client = $this->getClient();
        $client->createGeoIndex($this->collectionName, 'geofield');

        $document1 = $client->dispense($this->collectionName);
        $document1->set('name', 'Horacio Manuel Cartes Jara');
        $document1->set('geofield', array(48.1, 48.1));
        $doc1Id = $client->store($document1);

        $document2 = $client->dispense($this->collectionName);
        $document2->set('name', 'Tsegaye Kebede');
        $document2->set('geofield', array(48.0, 48.0));
        $doc2Id = $client->store($document2);

        $document3 = $client->dispense($this->collectionName);
        $document3->set('name', 'Barack Obama');
        $document3->set('geofield', array(50, 50));
        $doc3Id = $client->store($document3);

        $pod = $document1->getPod();

        $within = $pod->within(1000000, "FILTER myplaceholder.name IN [@barack, @horacio]", array("barack" => "Barack Obama", "horacio" => "Horacio Manuel Cartes Jara"), "myplaceholder");

        $this->assertInternalType('array', $within, "The result set should be an array");
        $this->assertCount(1, $within, "The result set should only contain 1 result");

        $result = reset($within);
        $this->assertEquals($document3->getPod()->getId(), $result->getId(), "The result's id does not match");
        $this->assertInternalType('float', $result->getDistance(), "The distance should be an integer");

        $referenceCoordinates = $result->getReferenceCoordinates();
        $this->assertEquals($document1->getPod()->getCoordinates(), $referenceCoordinates, "The reference coordinates do no match the reference's");
        $this->assertEquals($document1->getPod()->getId(), $result->getReferencePod()->getPod()->getId(), "The reference pod does not match the pod that ran the near() query");
    }

    /**
     * @covers Paradox\pod\Document::getModel
     */
    public function testGetModel()
    {
        $model = $this->getMockForAbstractClass('Paradox\AModel');

        $this->document->loadModel($model);

        $this->assertEquals($model, $this->document->getModel(), "The retrieved model is not the one we loaded into the pod");
    }

    /**
     * @covers Paradox\pod\Document::getReservedFields
     */
    public function testGetReservedFields()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Document');

        $getReservedFields = $reflectionClass->getMethod('getReservedFields');
        $getReservedFields->setAccessible(true);

        $result = $getReservedFields->invoke($this->document);

        $this->assertCount(3, $result, "The list of reserved fields should only contain 3 items");

        foreach ($result as $field) {
            $this->assertContains($field, array('_id', '_key', '_rev'), "The field $field is not a valid reserved field");
        }
    }

    /**
     * @covers Paradox\pod\Document::parseIdForKey
     */
    public function testParseIdForKey()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\pod\Document');

        $parseIdForKey = $reflectionClass->getMethod('parseIdForKey');
        $parseIdForKey->setAccessible(true);

        $result = $parseIdForKey->invoke($this->document, "mycollection/123456");

        $this->assertEquals('123456', $result, "There was a problem pasring the id");
    }

    /**
     * @covers Paradox\pod\Document::compareToolbox
     */
    public function testCompareToolbox()
    {
        $toolbox = $this->getClient()->getToolbox();

        $document = new Document($toolbox, "mycollection");

        $this->assertTrue($document->compareToolbox($toolbox), "The toolbox compared is referenced by the document, so should return true");

        $this->assertFalse($this->document->compareToolbox($toolbox), "The toolbox compared is not referenced by the document, so should return false");
    }

    /**
     * @covers Paradox\pod\Document::onEvent
     */
    public function testOnEvent()
    {
        $model = $this->getMock('Paradox\AModel');

        $model->expects($this->once())
        ->method('afterDispense');

        $model->expects($this->once())
        ->method('afterOpen');

        $model->expects($this->once())
        ->method('beforeStore');

        $model->expects($this->once())
        ->method('afterStore');

        $model->expects($this->once())
        ->method('beforeDelete');

        $model->expects($this->once())
        ->method('afterDelete');

        $this->document->loadModel($model);

        $events = array('after_dispense', 'after_open', 'before_store', 'after_store', 'before_delete', 'after_delete');

        foreach ($events as $event) {
            $this->document->onEvent(new Event($event, $this->document));
        }
    }

    /**
     * @covers Paradox\pod\Document::onEvent
     */
    public function testOnEventNeverCalled()
    {
        $model = $this->getMock('Paradox\AModel');

        $model->expects($this->never())
        ->method('afterDispense');

        $model->expects($this->never())
        ->method('afterOpen');

        $model->expects($this->never())
        ->method('beforeStore');

        $model->expects($this->never())
        ->method('afterStore');

        $model->expects($this->never())
        ->method('beforeDelete');

        $model->expects($this->never())
        ->method('afterDelete');

        $this->document->loadModel($model);

        $anotherDocument = $this->getClient()->dispense('mycollection');

        $events = array('after_dispense', 'after_open', 'before_store', 'after_store', 'before_delete', 'after_delete');

        foreach ($events as $event) {
            $this->document->onEvent(new Event($event, $anotherDocument->getPod()));
        }
    }
}
