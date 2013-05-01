<?php
namespace tests\Paradox\toolbox;
use tests\Base;
use Paradox\toolbox\PodManager;
use Paradox\exceptions\PodManagerException;
use Paradox\pod\Document;

/**
 * Tests for the pod manager.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class PodManagerTest extends Base
{
    /**
     * The collection name for this test case.
     * @var string
     */
    protected $collectionName = 'PodManagerTestCollection';

    /**
     * The graph name for this test case.
     * @var string
     */
    protected $graphName = 'PodManagerTestGraph';

    /**
     * Stores an instance of the pod manager.
     * @var PodManager
     */
    protected $podManager;

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
        $this->podManager = $this->getPodManager();
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
            //Ignore exception
        }

        try {
            $client->deleteGraph($this->graphName);
        } catch (\Exception $e) {
            //Ignore exception
        }
    }

    /**
     * Convinence function to get the pod manager.
     * @param  string                      $graph The name of the graph if we want the pod manager to manage a graph.
     * @return \Paradox\toolbox\PodManager
     */
    protected function getPodManager($graph = null)
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $graph);

        return $client->getToolbox()->getPodManager();
    }

    /**
     * @covers Paradox\toolbox\PodManager::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflection_class = new \ReflectionClass('Paradox\toolbox\PodManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $property = $reflection_class->getProperty('_toolbox');
        $property->setAccessible(true);

        //We need to create an empty object to pass to
        //ReflectionProperty's getValue method
        $manager = new PodManager($this->getClient()->getToolbox());

        $this->assertInstanceOf('Paradox\Toolbox', $property->getValue($manager), 'PodManager constructor did not store a Paradox\Toolbox.');
    }

    /**
     * @covers Paradox\toolbox\PodManager::dispense
     */
    public function testDispenseDocument()
    {
        $document = $this->podManager->dispense($this->collectionName);

        $this->assertInstanceOf('Paradox\AModel', $document, 'Dispensed document should have type Paradox\AModel');
    }

    /**
     * @covers Paradox\toolbox\PodManager::dispense
     */
    public function testDispenseDocumentWithLabel()
    {
        try {
            $document = $this->podManager->dispense($this->collectionName, 'mylabel');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodManagerException', $e, 'Exception was not of type Paradox\exceptions\PodManagerException');

            return;
        }

        $this->fail('An exception was not thrown when we dispensed a normal document with a label');
    }

    /**
     * @covers Paradox\toolbox\PodManager::dispense
     */
    public function testDispenseEdgeWithLabel()
    {
        $manager = $this->getPodManager($this->graphName);

        $edge = $manager->dispense('edge', 'mylabel');

        $this->assertInstanceOf('Paradox\AModel', $edge, 'Dispensed edge should be of type Paradox\AModel');
        $this->assertEquals('mylabel', $edge->getPod()->getLabel(), "Dispensed edge's label does not match.");
    }

    /**
     * @covers Paradox\toolbox\PodManager::dispense
     */
    public function testDispenseEdgeWithoutLabel()
    {
        $manager = $this->getPodManager($this->graphName);

        $edge = $manager->dispense('edge');

        $this->assertInstanceOf('Paradox\AModel', $edge, 'Dispensed edge should be of type Paradox\AModel');
        $this->assertNull($edge->getPod()->getLabel(), "Dispensed edge's label does not match.");
    }

    /**
     * @covers Paradox\toolbox\PodManager::dispense
     */
    public function testDispenseVertex()
    {
        $manager = $this->getPodManager($this->graphName);

        $edge = $manager->dispense('vertex');

        $this->assertInstanceOf('Paradox\AModel', $edge, 'Dispensed edge should be of type Paradox\AModel');
    }

    /**
     * @covers Paradox\toolbox\PodManager::dispense
     */
    public function testDispenseVertexWithLabel()
    {
        $manager = $this->getPodManager($this->graphName);

        try {
            $vertex = $manager->dispense('vertex', 'mylabel');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodManagerException', $e, 'Exception was not of type Paradox\exceptions\PodManagerException');

            return;
        }

        $this->fail('An exception was not thrown when we dispensed a vertex with a label');
    }

    /**
     * @covers Paradox\toolbox\PodManager::dispense
     */
    public function testDispenseInvalidTypeInGraph()
    {
        $manager = $this->getPodManager($this->graphName);

        try {
            $vertex = $manager->dispense($this->collectionName);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodManagerException', $e, 'Exception was not of type Paradox\exceptions\PodManagerException');

            return;
        }

        $this->fail('An exception was not thrown when we dispensed a type that is not "vertex" or "edge" in a graph');
    }

    /**
     * @covers Paradox\toolbox\PodManager::store
     */
    public function testStoreInUnknownCollection()
    {
        $document = $this->podManager->dispense('CollectionThatDoesNotExist');

        try {
            $id = $this->podManager->store($document);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodManagerException', $e, 'Exception was not of type Paradox\exception\PodManagerException');

            return;
        }
        $this->fail('An exception was not thrown when we tried to store a document in a collection that does not exist.');
    }

    /**
     * @covers Paradox\toolbox\PodManager::store
     */
    public function testStoreAndUpdateDocument()
    {
        $document = $this->podManager->dispense($this->collectionName);
        $document->set('name', 'john smith');
        $id = $this->podManager->store($document);

        $this->assertNotNull($id, "Id of saved vertex should not be null.");

        //Update the document
        $document->set('name', 'david smith');
        $id = $this->podManager->store($document);
        $this->assertNotNull($id, "Id of saved vertex should not be null.");
    }

    /**
     * @covers Paradox\toolbox\PodManager::store
     */
    public function testStoreAndUpdateEdge()
    {
        $manager = $this->getPodManager($this->graphName);
        $vertex1 = $manager->dispense('vertex');
        $vertex2= $manager->dispense('vertex');

        $edge = $manager->dispense('edge');
        $edge->set('name', 'john smith');
        $edge->setFrom($vertex1);
        $edge->setTo($vertex2);
        $id = $manager->store($edge);
        $this->assertNotNull($id, "Stored edge id should not be null");

        $vertex3= $manager->dispense('vertex');
        $edge->set('name', 'david smith');
        $edge->setFrom($vertex3);
        $id = $manager->store($edge);
        $this->assertNotNull($id, "Stored edge id should not be null");
    }

    /**
     * @covers Paradox\toolbox\PodManager::store
     */
    public function testStoreEdgeWithNoFrom()
    {
        $manager = $this->getPodManager($this->graphName);
        $edge = $manager->dispense('edge');
        $vertex = $manager->dispense('vertex');
        $edge->setTo($vertex);

        try {
            $id = $manager->store($edge);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodManagerException', $e, 'Exception was not of type Paradox\exceptions\PodManagerException');

            return;
        }

        $this->fail('An exception was not thrown when we tried to save an edge without "from" and "to" vertices');
    }

    /**
     * @covers Paradox\toolbox\PodManager::store
     */
    public function testStoreEdgeWithNoTo()
    {
        $manager = $this->getPodManager($this->graphName);
        $edge = $manager->dispense('edge');
        $vertex = $manager->dispense('vertex');
        $edge->setFrom($vertex);

        try {
            $id = $manager->store($edge);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodManagerException', $e, 'Exception was not of type Paradox\exceptions\PodManagerException');

            return;
        }

        $this->fail('An exception was not thrown when we tried to save an edge without "from" and "to" vertices');
    }

    /**
     * @covers Paradox\toolbox\PodManager::store
     */
    public function testStoreAndUpdateVertex()
    {
        $manager = $this->getPodManager($this->graphName);

        $vertex = $manager->dispense('vertex');
        $vertex->set('name', 'john smith');
        $id = $manager->store($vertex);

        $this->assertNotNull($id, "Id of saved vertex should not be null.");

        $vertex->set('name', 'david smith');
        $id = $manager->store($vertex);
    }

    /**
     * @covers Paradox\toolbox\PodManager::delete
     */
    public function testDeleteDocument()
    {
        $document = $this->podManager->dispense($this->collectionName);
        $id = $this->podManager->store($document);
        $this->assertNotNull($id, "Id of stored document should not be null");

        $this->podManager->delete($document);

        $retrievedDocument = $this->podManager->load($this->collectionName, $id);
        $this->assertNull($retrievedDocument, "We deleted the document, but was still able to retrieve it?");
    }

    /**
     * @covers Paradox\toolbox\PodManager::delete
     */
    public function testDeleteNonExistingDocument()
    {
        $document = $this->podManager->dispense($this->collectionName);

        try {
            $this->podManager->delete($document);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodManagerException', $e, 'Exception was not of type Paradox\exceptions\PodManagerException');

            return;
        }

        $this->fail('An exception was not thrown when we tried to delete a non-existing document.');
    }

    /**
     * @covers Paradox\toolbox\PodManager::delete
     */
    public function testDeleteVertex()
    {
        $manager = $this->getPodManager($this->graphName);
        $vertex = $manager->dispense("vertex");
        $id = $manager->store($vertex);
        $this->assertNotNull($id, "Id of stored document should not be null");

        $manager->delete($vertex);

        $retrievedDocument = $manager->load('vertex', $id);
        $this->assertNull($retrievedDocument, "We deleted the document, but was still able to retrieve it?");
    }

    /**
     * @covers Paradox\toolbox\PodManager::delete
     */
    public function testDeleteEdge()
    {
        $manager = $this->getPodManager($this->graphName);

        $edge = $manager->dispense("edge");
        $vertex1 = $manager->dispense("vertex");
        $vertex2 = $manager->dispense("vertex");
        $edge->setFrom($vertex1);
        $edge->setTo($vertex2);

        $id = $manager->store($edge);
        $this->assertNotNull($id, "Id of stored document should not be null");

        $manager->delete($edge);

        $retrievedDocument = $manager->load('edge', $id);
        $this->assertNull($retrievedDocument, "We deleted the document, but was still able to retrieve it?");
    }

    /**
     * @covers Paradox\toolbox\PodManager::load
     */
    public function testLoadDocument()
    {
        $document = $this->podManager->dispense($this->collectionName);
        $document->set('name', 'john smith');

        $id = $this->podManager->store($document);
        $this->assertNotNull($id, "Id of stored document should not be null");

        $loadedDocument = $this->podManager->load($this->collectionName, $id);
        $this->assertInstanceOf('Paradox\AModel', $loadedDocument, 'The loaded document is not a Paradox\AModel');

        $this->assertEquals('john smith', $loadedDocument->get('name'), 'The data inside the loaded document does not match');

        $pod = $loadedDocument->getPod();
        $this->assertInstanceOf('Paradox\pod\Document', $pod, 'The pod in the loaded document is not a Paradox\pod\Document');
    }

    /**
     * @covers Paradox\toolbox\PodManager::load
     */
    public function testLoadVertex()
    {
        $manager = $this->getPodManager($this->graphName);
        $vertex = $manager->dispense("vertex");
        $vertex->set('name', 'john smith');

        $id = $manager->store($vertex);
        $this->assertNotNull($id, "Id of stored vertex should not be null");

        $loadedDocument = $manager->load("vertex", $id);
        $this->assertInstanceOf('Paradox\AModel', $loadedDocument, 'The loaded vertex is not a Paradox\AModel');

        $this->assertEquals('john smith', $loadedDocument->get('name'), 'The data inside the loaded vertex does not match');

        $pod = $loadedDocument->getPod();
        $this->assertInstanceOf('Paradox\pod\Vertex', $pod, 'The pod in the loaded vertex is not a Paradox\pod\Vertex');
    }

    /**
     * @covers Paradox\toolbox\PodManager::load
     */
    public function testLoadEdge()
    {
        $manager = $this->getPodManager($this->graphName);
        $edge = $manager->dispense("edge");
        $edge->set('name', 'john smith');

        $vertex1 = $manager->dispense("vertex");
        $vertex2 = $manager->dispense("vertex");

        $edge->setFrom($vertex1);
        $edge->setTo($vertex2);

        $id = $manager->store($edge);
        $this->assertNotNull($id, "Id of stored edge should not be null");

        $loadedDocument = $manager->load("edge", $id);
        $this->assertInstanceOf('Paradox\AModel', $loadedDocument, 'The loaded edge is not a Paradox\AModel');

        $this->assertEquals('john smith', $loadedDocument->get('name'), 'The data inside the loaded edge does not match');

        $pod = $loadedDocument->getPod();
        $this->assertInstanceOf('Paradox\pod\Edge', $pod, 'The pod in the loaded edge is not a Paradox\pod\Edge');
    }

    /**
     * @covers Paradox\toolbox\PodManager::load
     */
    public function testLoadInvalidTypeInGraph()
    {
        $manager = $this->getPodManager($this->graphName);

        try {
            $edge = $manager->load("notAnEdgeOrVertex", "123456");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodManagerException', $e, 'Exception was not of type Paradox\exceptions\PodManagerException');

            return;
        }
        $this->fail('An exception was not thrown when we tried to load an type that is not a "vertex" or "edge" in a graph');
    }

    /**
     * @covers Paradox\toolbox\PodManager::load
     */
    public function testLoadNonexistingDocument()
    {
        $document = $this->podManager->load($this->collectionName, '123456');
        $this->assertNull($document, 'A non-existing document resulting in something being loaded.');
    }

    /**
     * @covers Paradox\toolbox\PodManager::convertToPods
     * @covers Paradox\toolbox\PodManager::convertDriverDocumentToPod
     */
    public function testConvertDriverDocumentToPods()
    {
        $document = $this->podManager->dispense($this->collectionName);
        $document->set('name', 'john smith');
        $this->podManager->store($document);

        $driverDocument = $document->getPod()->toDriverDocument();

        $converted = $this->podManager->convertToPods($this->collectionName, array($driverDocument));
        $convertedDocument = reset($converted);

        //We cannot simply do an assert equals between the 2 objects, because the ArangoDB-PHP introduces a _isNew property which does not exist in our pod.
        $this->assertEquals($document->get('name'), $convertedDocument->get('name'), "Converted document pod's name does not match the original.");
        $this->assertEquals($document->getId(), $convertedDocument->getId(), "Converted document pod's id does not match the original.");
        $this->assertEquals($document->getKey(), $convertedDocument->getKey(), "Converted document pod's key does not match the original.");
        $this->assertEquals($document->getRevision(), $convertedDocument->getRevision(), "Converted document pod's revision does not match the original.");
    }

    /**
     * @covers Paradox\toolbox\PodManager::convertToPods
     * @covers Paradox\toolbox\PodManager::convertDriverDocumentToPod
     */
    public function testConvertDriverEdgeToPods()
    {
        $manager = $this->getPodManager($this->graphName);
        $edge = $manager->dispense("edge");
        $vertex1 = $manager->dispense("vertex");
        $vertex2 = $manager->dispense("vertex");
        $edge->setFrom($vertex1);
        $edge->setTo($vertex2);
        $edge->set('name', 'john smith');

        $manager->store($edge);

        $driverEdge = $edge->getPod()->toDriverDocument();

        $converted = $manager->convertToPods("edge", array($driverEdge));
        $convertedEdge = reset($converted);

        //We cannot simply do an assert equals between the 2 objects, because the _from and _to of the converted object will
        //be null as they are lazy loaded.

        $this->assertEquals($edge->get('name'), $convertedEdge->get('name'), "Converted edge pod's name does not match the original.");
        $this->assertEquals($edge->getId(), $convertedEdge->getId(), "Converted edge pod's id does not match the original.");
        $this->assertEquals($edge->getKey(), $convertedEdge->getKey(), "Converted edge pod's key does not match the original.");
        $this->assertEquals($edge->getRevision(), $convertedEdge->getRevision(), "Converted edge pod's revision does not match the original.");
    }

    /**
     * @covers Paradox\toolbox\PodManager::convertToPods
     * @covers Paradox\toolbox\PodManager::convertDriverDocumentToPod
     */
    public function testConvertDriverVertexToPods()
    {
        $manager = $this->getPodManager($this->graphName);
        $vertex = $manager->dispense("vertex");
        $vertex->set('name', 'john smith');
        $manager->store($vertex);

        $driverVertex = $vertex->getPod()->toDriverDocument();

        $converted = $manager->convertToPods("vertex", array($driverVertex));
        $convertedVertex = reset($converted);

        //We cannot simply do an assert equals between the 2 objects, because the ArangoDB-PHP introduces a _isNew property which does not exist in our pod.
        $this->assertEquals($vertex->get('name'), $convertedVertex->get('name'), "Converted vertex pod's name does not match the original.");
        $this->assertEquals($vertex->getId(), $convertedVertex->getId(), "Converted vertex pod's id does not match the original.");
        $this->assertEquals($vertex->getKey(), $convertedVertex->getKey(), "Converted vertex pod's key does not match the original.");
        $this->assertEquals($vertex->getRevision(), $convertedVertex->getRevision(), "Converted vertex pod's revision does not match the original.");
    }

    /**
     * @covers Paradox\toolbox\PodManager::convertToPods
     * @covers Paradox\toolbox\PodManager::convertArrayToPod
     */
    public function testConvertArrayDocumentToPods()
    {
        $data = array(
                    '_id' => "{$this->collectionName}/123456",
                    '_rev' => '123456',
                    '_key' => '123456',
                    'name' => "john smith"
                );

        $converted = $this->podManager->convertToPods($this->collectionName, array($data));
        $convertedPod = reset($converted);

        $this->assertEquals($data['_id'], $convertedPod->getId(), "Converted document pod's id does not match the original.");
        $this->assertEquals($data['name'], $convertedPod->get('name'), "Converted document pod's name does not match the original.");
    }

    /**
     * @covers Paradox\toolbox\PodManager::convertToPods
     * @covers Paradox\toolbox\PodManager::convertArrayToPod
     */
    public function testConvertArrayDocumentWithoutIdToPods()
    {
        $data = array('name' => "john smith");

        $converted = $this->podManager->convertToPods($this->collectionName, array($data));
        $convertedPod = reset($converted);

        $this->assertEquals($data['name'], $convertedPod->get('name'), "Converted document pod's name does not match the original.");
    }

    /**
     * @covers Paradox\toolbox\PodManager::createVertex
     */
    public function testCreateVertex()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\PodManager');

        $method = $reflectionClass->getMethod('createVertex');
        $method->setAccessible(true);

        $manager = new PodManager($this->getClient()->getToolbox());

        $vertex = $method->invoke($manager);
        $this->assertInstanceOf('Paradox\AModel', $vertex, 'An Paradox\AModel was not created');

        $this->assertInstanceOf('Paradox\pod\Vertex', $vertex->getPod(), 'The inner pod was not of the type Paradox\pod\Vertex');
    }

    /**
     * @covers Paradox\toolbox\PodManager::createEdge
     */
    public function testCreateEdge()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\PodManager');

        $method = $reflectionClass->getMethod('createEdge');
        $method->setAccessible(true);

        $manager = new PodManager($this->getClient()->getToolbox());

        $edge = $method->invoke($manager);
        $this->assertInstanceOf('Paradox\AModel', $edge, 'An Paradox\AModel was not created');

        $this->assertInstanceOf('Paradox\pod\Edge', $edge->getPod(), 'The inner pod was not of the type Paradox\pod\Edge');
    }

    /**
     * @covers Paradox\toolbox\PodManager::createDocument
     */
    public function testCreateDocument()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\PodManager');

        $method = $reflectionClass->getMethod('createDocument');
        $method->setAccessible(true);

        $manager = new PodManager($this->getClient()->getToolbox());

        $document = $method->invoke($manager, $this->collectionName);
        $this->assertInstanceOf('Paradox\AModel', $document, 'An Paradox\AModel was not created');

        $this->assertInstanceOf('Paradox\pod\Document', $document->getPod(), 'The inner pod was not of the type Paradox\pod\Document');
    }

    /**
     * @covers Paradox\toolbox\PodManager::attachEventsToPod
     */
    public function testAttachEventsToPod()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\PodManager');

        $method = $reflectionClass->getMethod('attachEventsToPod');
        $method->setAccessible(true);

        $notify = $reflectionClass->getMethod('notify');
        $notify->setAccessible(true);

        $client = $this->getClient();

        $manager = new PodManager($client->getToolbox());
        $document = new Document($client->getToolbox(), 'mycollection');

        $method->invoke($manager, $document); //Attach the events

        //Setup the mock model
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

        //Load the model into the document
        $document->loadModel($model);

        //Fire all the events to check if the model is called for each of them
        $events = array('after_dispense', 'after_open', 'before_store', 'after_store', 'before_delete', 'after_delete');

        foreach ($events as $event) {
            $notify->invoke($manager, $event, $document);
        }
    }

    /**
     * @covers Paradox\toolbox\PodManager::setupModel
     */
    public function testSetupModel()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\PodManager');

        $setupModelMethod = $reflectionClass->getMethod('setupModel');
        $setupModelMethod->setAccessible(true);

        $property = $reflectionClass->getProperty('_toolbox');
        $property->setAccessible(true);

        $manager = new PodManager($this->getClient()->getToolbox());

        $pod = new Document($property->getValue($manager), $this->collectionName);

        $model = $setupModelMethod->invoke($manager, $this->collectionName, $pod);
        $this->assertInstanceOf('Paradox\AModel', $model, 'An Paradox\AModel was not created');

        $this->assertInstanceOf('Paradox\pod\Document', $model->getPod(), 'The inner pod was not of the type Paradox\pod\Document');
        $this->assertInstanceOf('Paradox\AModel', $model->getPod()->getModel(), 'The model referenced by the pod was not of the type Paradox\pod\Document');
    }

    /**
     * @covers Paradox\toolbox\PodManager::setupModel
     */
    public function testSetupInvalidModel()
    {
        //Create the mock model formatter
        $modelFormatter = $this->getMock('Paradox\IModelFormatter');

        $modelFormatter->expects($this->any())
                       ->method('formatModel')
                       ->will($this->returnValue('\stdClass')); //Return something that is definitely not a descendent of AModel

        $client = $this->getClient();
        $client->setModelFormatter($modelFormatter);

        try {
            $pod = $client->getToolbox()->getPodManager()->dispense($this->collectionName);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\PodManagerException', $e, 'Exception was not of the type Paradox\exceptions\PodManagerException');

            return;
        }

        $this->fail('An exception was not thrown when we tried to instantiate a model that does not inherit from Paradox\AModel');
    }

    /**
     * @covers Paradox\toolbox\PodManager::validateType
     */
    public function testValidateDocumentType()
    {
        $result = $this->podManager->validateType($this->collectionName);

        $this->assertTrue($result, "Document collection type was validated as false when it should be true");
    }

    /**
     * @covers Paradox\toolbox\PodManager::validateType
     */
    public function testValidateGraphTypes()
    {
        $manager = $this->getPodManager($this->graphName);
        $result = $manager->validateType("vertex");

        $this->assertTrue($result, "Graph vertex type was validated as false when it should be true");

        $result = $manager->validateType("edge");

        $this->assertTrue($result, "Graph edge type was validated as false when it should be true");
    }

    /**
     * @covers Paradox\toolbox\PodManager::validateType
     */
    public function testValidateInvalidGraphType()
    {
        $manager = $this->getPodManager($this->graphName);
        $result = $manager->validateType("NotAVertexOrEdge");

        $this->assertFalse($result, "Invalid graph type was validated as true");
    }
}
