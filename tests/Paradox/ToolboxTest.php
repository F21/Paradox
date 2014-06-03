<?php
namespace tests\Paradox;
use tests\Base;
use Paradox\Debug;
use Paradox\DefaultModelFormatter;
use Paradox\Toolbox;
use Paradox\pod\Document;

/**
 * Tests for the toolbox.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class ToolboxTest extends Base
{
    /**
     * The collection name for this test case.
     * @var string
     */
    protected $collectionName = 'ToolboxTestCollection';

    /**
     * The graph name for this test case.
     * @var string
     */
    protected $graphName = 'ToolboxTestGraph';

    /**
     * Stores an instance of the toolbox.
     * @var Toolbox
     */
    protected $toolbox;

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

        $this->toolbox = $this->getToolbox();
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
    }

    /**
     * Convinence function to get the toolbox.
     */
    protected function getToolbox($graph = null)
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $graph);

        return $client->getToolbox();
    }

    /**
     * @covers Paradox\Toolbox::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\Toolbox');

        //Then we need to get the property we wish to test
        //and make it accessible
        $endpointProperty = $reflectionClass->getProperty('_endpoint');
        $endpointProperty->setAccessible(true);

        $usernameProperty = $reflectionClass->getProperty('_username');
        $usernameProperty->setAccessible(true);

        $passwordProperty = $reflectionClass->getProperty('_password');
        $passwordProperty->setAccessible(true);

        $graphProperty = $reflectionClass->getProperty('_graph');
        $graphProperty->setAccessible(true);

        $debugProperty = $reflectionClass->getProperty('_debug');
        $debugProperty->setAccessible(true);

        $formatterProperty = $reflectionClass->getProperty('_formatter');
        $formatterProperty->setAccessible(true);

        $debugger = new Debug();
        $formatter = new DefaultModelFormatter();

        $toolbox = new Toolbox($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), 'mygraph', $debugger, $formatter);

        $this->assertEquals($this->getDefaultEndpoint(), $endpointProperty->getValue($toolbox), "The endpoint does not match");
        $this->assertEquals($this->getDefaultUsername(), $usernameProperty->getValue($toolbox), "The username does not match");
        $this->assertEquals($this->getDefaultPassword(), $passwordProperty->getValue($toolbox), "The password does not match");
        $this->assertEquals('mygraph', $graphProperty->getValue($toolbox), "The graph does not match");
        $this->assertEquals($debugger, $debugProperty->getValue($toolbox), "The debugger does not match");
        $this->assertEquals($formatter, $formatterProperty->getValue($toolbox), "The formatter does not match");

        //Check to see that the constructor has created all the tools in the toolbox
        $finderProperty = $reflectionClass->getProperty('_finder');
        $finderProperty->setAccessible(true);

        $podManagerProperty = $reflectionClass->getProperty('_podManager');
        $podManagerProperty->setAccessible(true);

        $collectionManagerProperty = $reflectionClass->getProperty('_collectionManager');
        $collectionManagerProperty->setAccessible(true);

        $queryProperty = $reflectionClass->getProperty('_query');
        $queryProperty->setAccessible(true);

        $serverProperty = $reflectionClass->getProperty('_server');
        $serverProperty->setAccessible(true);

        $graphManagerProperty = $reflectionClass->getProperty('_graphManager');
        $graphManagerProperty->setAccessible(true);

        $transactionManagerProperty = $reflectionClass->getProperty('_transactionManager');
        $transactionManagerProperty->setAccessible(true);

        $this->assertInstanceOf('Paradox\toolbox\Finder', $finderProperty->getValue($toolbox), "The finder was not instantiated properly");
        $this->assertInstanceOf('Paradox\toolbox\PodManager', $podManagerProperty->getValue($toolbox), "The pod manager was not instantiated properly");
        $this->assertInstanceOf('Paradox\toolbox\CollectionManager', $collectionManagerProperty->getValue($toolbox), "The collection manager was not instantiated properly");
        $this->assertInstanceOf('Paradox\toolbox\Query', $queryProperty->getValue($toolbox), "The query helper was not instantiated properly");
        $this->assertInstanceOf('Paradox\toolbox\Server', $serverProperty->getValue($toolbox), "The server manager was not instantiated properly");
        $this->assertInstanceOf('Paradox\toolbox\GraphManager', $graphManagerProperty->getValue($toolbox), "The graph manager was not instantiated properly");
        $this->assertInstanceOf('Paradox\toolbox\TransactionManager', $transactionManagerProperty->getValue($toolbox), "The transaction manager was not instantiated properly");
    }

    /**
     * @covers Paradox\Toolbox::getEndpoint
     */
    public function testGetEndpoint()
    {
        $this->assertEquals($this->getDefaultEndpoint(), $this->toolbox->getEndpoint(), "The endpoint does not match");
    }

    /**
     * @covers Paradox\Toolbox::getUsername
     */
    public function testGetUsername()
    {
        $this->assertEquals($this->getDefaultUsername(), $this->toolbox->getUsername(), "The username does not match");
    }

    /**
     * @covers Paradox\Toolbox::getPassword
     */
    public function testGetPassword()
    {
        $this->assertEquals($this->getDefaultPassword(), $this->toolbox->getPassword(), "The password does not match");
    }

    /**
     * @covers Paradox\Toolbox::getGraph
     */
    public function testGetGraph()
    {
        $toolbox = $this->getToolbox('mygraph');
        $this->assertEquals('mygraph', $toolbox->getGraph(), "The graph does not match");
    }

    /**
     * @covers Paradox\Toolbox::getGraph
     */
    public function testGetGraphOnToolboxWithoutGraph()
    {
        $this->assertNull($this->toolbox->getGraph(), "The graph should be null since this toolbox does not manage a graph");
    }

    /**
     * @covers Paradox\Toolbox::isGraph
     */
    public function testIsGraph()
    {
        $toolbox = $this->getToolbox('mygraph');
        $this->assertTrue($toolbox->isGraph(), "The toolbox manages a graph, so isGraph() should return true");
    }

    /**
     * @covers Paradox\Toolbox::isGraph
     */
    public function testIsGraphOnToolboxNotManagingAGraph()
    {
        $this->assertFalse($this->toolbox->isGraph(), "The toolbox does not manage a graph, so isGraph() should return false");
    }

    /**
     * @covers Paradox\Toolbox::getVertexCollectionName
     */
    public function testGetVertexCollectionName()
    {
        $toolbox = $this->getToolbox('mygraph');
        $this->assertEquals('mygraphVertexCollection', $toolbox->getVertexCollectionName(), "The vertex collection name does not match");
    }

    /**
     * @covers Paradox\Toolbox::getVertexCollectionName
     */
    public function testGetVertexCollectionNameOnNonGraphToolbox()
    {
        try {
            $this->toolbox->getVertexCollectionName();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ToolboxException', $e, 'Exception thrown was not a Paradox\exceptions\ToolboxException');

            return;
        }

        $this->fail('Calling getVertexCollectionName() on a non-graph toolbox did not throw an exception');
    }

    /**
     * @covers Paradox\Toolbox::getEdgeCollectionName
     */
    public function testGetEdgeCollectionName()
    {
        $toolbox = $this->getToolbox('mygraph');
        $this->assertEquals('mygraphEdgeCollection', $toolbox->getEdgeCollectionName(), "The edge collection name does not match");
    }

    /**
     * @covers Paradox\Toolbox::getEdgeCollectionName
     */
    public function testGetEdgeCollectionNameOnNonGraphToolbox()
    {
        try {
            $this->toolbox->getEdgeCollectionName();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ToolboxException', $e, 'Exception thrown was not a Paradox\exceptions\ToolboxException');

            return;
        }

        $this->fail('Calling getEdgeCollectionName() on a non-graph toolbox did not throw an exception');
    }

    /**
     * @covers Paradox\Toolbox::getPodManager
     */
    public function testGetPodManager()
    {
        $this->assertInstanceOf('Paradox\toolbox\PodManager', $this->toolbox->getPodManager(), 'Getting the pod manager did not return a Paradox\toolbox\PodManager');
    }

    /**
     * @covers Paradox\Toolbox::getCollectionManager
     */
    public function testGetCollectionManager()
    {
        $this->assertInstanceOf('Paradox\toolbox\CollectionManager', $this->toolbox->getCollectionManager(), 'Getting the collection manager did not return a Paradox\toolbox\CollectionManager');
    }

    /**
     * @covers Paradox\Toolbox::getGraphManager
     */
    public function testGetGraphManager()
    {
        $this->assertInstanceOf('Paradox\toolbox\GraphManager', $this->toolbox->getGraphManager(), 'Getting the graph manager did not return a Paradox\toolbox\GraphManager');
    }

    /**
     * @covers Paradox\Toolbox::getFinder
     */
    public function testGetFinder()
    {
        $this->assertInstanceOf('Paradox\toolbox\Finder', $this->toolbox->getFinder(), 'Getting the finder did not return a Paradox\toolbox\Finder');
    }

    /**
     * @covers Paradox\Toolbox::getQuery
     */
    public function testGetQuery()
    {
        $this->assertInstanceOf('Paradox\toolbox\Query', $this->toolbox->getQuery(), 'Getting the query helper did not return a Paradox\toolbox\Query');
    }

    /**
     * @covers Paradox\Toolbox::getServer
     */
    public function testGetServer()
    {
        $this->assertInstanceOf('Paradox\toolbox\Server', $this->toolbox->getServer(), 'Getting the server manager did not return a Paradox\toolbox\Server');
    }

    /**
     * @covers Paradox\Toolbox::getTransactionManager
     */
    public function testGetTransactionManager()
    {
        $this->assertInstanceOf('Paradox\toolbox\TransactionManager', $this->toolbox->getTransactionManager(), 'Getting the transaction manager did not return a Paradox\toolbox\TransactionManager');
    }

    /**
     * @covers Paradox\Toolbox::formatModel
     */
    public function testFormatModel()
    {
        $formatter = $this->getMock('Paradox\IModelFormatter');

        $formatter->expects($this->any())
                  ->method('formatModel')
                  ->will($this->returnValue('SomeModel'));

        $client = $this->getClient();
        $client->setModelFormatter($formatter);
        
        $document = new Document($client->getToolbox(), 'sometype');

        $this->assertEquals('SomeModel', $client->getToolbox()->formatModel($document), "The formatted model does not match");
    }

    /**
     * @covers Paradox\Toolbox::validatePod
     */
    public function testValidatePod()
    {
        $client = $this->getClient();

        $document = $client->dispense("mycollection");

        $this->assertTrue($client->getToolbox()->validatePod($document), "The pod should validate as true");
    }

    /**
     * @covers Paradox\Toolbox::validatePod
     */
    public function testValidatePodFailure()
    {
        $client = $this->getClient();

        $document = $client->dispense("mycollection");

        try {
            $this->toolbox->validatePod($document);

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ToolboxException', $e, 'Exception thrown was not a Paradox\exceptions\ToolboxException');

            return;
        }

        $this->fail('Calling validatePod() on a pod that does not belong to the toolbox did not throw an exception');
    }

    /**
     * @covers Paradox\Toolbox::getConnection
     */
    public function testGetConnection()
    {
        $connection = $this->toolbox->getConnection();
        $this->assertInstanceOf('triagens\ArangoDb\Connection', $connection, 'getConnection() did not return a triagens\ArangoDb\Connection');
    }

    /**
     * @covers Paradox\Toolbox::getDocumentHandler
     */
    public function testGetDocumentHandler()
    {
        $documentHandler = $this->toolbox->getDocumentHandler();
        $this->assertInstanceOf('triagens\ArangoDb\DocumentHandler', $documentHandler, 'getDocumentHandler() did not return a triagens\ArangoDb\DocumentHandler');
    }

    /**
     * @covers Paradox\Toolbox::getGraphHandler
     */
    public function testGetGraphHandler()
    {
        $graphHandler = $this->toolbox->getGraphHandler();
        $this->assertInstanceOf('triagens\ArangoDb\GraphHandler', $graphHandler, 'getGraphHandler() did not return a triagens\ArangoDb\GraphHandler');
    }

    /**
     * @covers Paradox\Toolbox::getCollectionHandler
     */
    public function testGetCollectionHandler()
    {
        $collectionHandler = $this->toolbox->getCollectionHandler();
        $this->assertInstanceOf('triagens\ArangoDb\CollectionHandler', $collectionHandler, 'getCollectionHandler() did not return a triagens\ArangoDb\CollectionHandler');
    }

    /**
     * @covers Paradox\Toolbox::getUserHandler
     */
    public function testGetUserHandler()
    {
        $userHandler = $this->toolbox->getUserHandler();
        $this->assertInstanceOf('triagens\ArangoDb\UserHandler', $userHandler, 'getUserHandler() did not return a triagens\ArangoDb\UserHandler');
    }

    /**
     * @covers Paradox\Toolbox::getAdminHandler
     */
    public function testGetAdminHandler()
    {
        $adminHandler = $this->toolbox->getAdminHandler();
        $this->assertInstanceOf('triagens\ArangoDb\AdminHandler', $adminHandler, 'getAdminHandler() did not return a triagens\ArangoDb\AdminHandler');
    }

    /**
     * @covers Paradox\Toolbox::getTransactionObject
     */
    public function testGetTransactionObject()
    {
        $transaction = $this->toolbox->getTransactionObject();
        $this->assertInstanceOf('triagens\ArangoDb\Transaction', $transaction, 'getTransactionObject() did not return a triagens\ArangoDb\Transaction');
    }

    /**
     * @covers Paradox\Toolbox::getDriver
     */
    public function testGetDriver()
    {
        $driver = $this->toolbox->getDriver();
        $this->assertInstanceOf('triagens\ArangoDb\DocumentHandler', $driver, 'getDriver() did not return a triagens\ArangoDb\DocumentHandler');
    }

    /**
     * @covers Paradox\Toolbox::getDriver
     */
    public function testGetDriverForGraph()
    {
        $toolbox = $this->getToolbox('mygraph');
        $driver = $toolbox->getDriver();
        $this->assertInstanceOf('triagens\ArangoDb\GraphHandler', $driver, 'getDriver() did not return a triagens\ArangoDb\GraphHandler for a toolbox that manages graphs');
    }

    /**
     * @covers Paradox\Toolbox::generateBindingParameter
     */
    public function testGenerateBindingParameter()
    {
        $parameter = $this->toolbox->generateBindingParameter('myparameter', array('anotherparameter' => 'somevalue'));

        $this->assertEquals('myparameter', $parameter, '"myparameter" was not used when there were no clashing parameter names');
    }

    /**
     * @covers Paradox\Toolbox::generateBindingParameter
     */
    public function testGenerateBindingParameterWithClashingBindingParameter()
    {
        $parameter = $this->toolbox->generateBindingParameter('myparameter', array('myparameter' => 'somevalue'));

        $this->assertNotEquals('myparameter', $parameter, '"myparameter" was used when there was a clashing parameter names');
    }

    /**
     * @covers Paradox\Toolbox::parseIdForKey
     */
    public function testParseIdForKey()
    {
        $key = $this->toolbox->parseIdForKey('mycollection/123456');
        $this->assertEquals('123456', $key, "The parsed key does not match");
    }

    /**
     * @covers Paradox\Toolbox::parseId
     */
    public function testParseId()
    {
        $parsed = $this->toolbox->parseId('mycollection/123456');

        $this->assertEquals('mycollection', $parsed['collection'], "The parsed collection does not match");
        $this->assertEquals('123456', $parsed['key'], "The parsed key does not match");
    }

    /**
     * @covers Paradox\Toolbox::setModelFormatter
     */
    public function testSetModelFormatter()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\Toolbox');

        //Then we need to get the property we wish to test
        //and make it accessible
        $formatterProperty = $reflectionClass->getProperty('_formatter');
        $formatterProperty->setAccessible(true);

        $formatter = $this->getMock('Paradox\IModelFormatter');

        $this->toolbox->setModelFormatter($formatter);

        $this->assertEquals($formatter, $formatterProperty->getValue($this->toolbox), "The formatter returned does not match the one we set into the toolbox");
    }

    /**
     * @covers Paradox\Toolbox::normaliseDriverExceptions
     */
    public function testNormaliseDriverExceptions()
    {
        $driverServerException = new \triagens\ArangoDb\ServerException();

        $details[\triagens\ArangoDb\ServerException::ENTRY_CODE] = 123456;
        $details[\triagens\ArangoDb\ServerException::ENTRY_MESSAGE] = "some error message";
        $driverServerException->setDetails($details);

        $result = $this->toolbox->normaliseDriverExceptions($driverServerException);
        $this->assertEquals(123456, $result['code']);
        $this->assertEquals("some error message", $result['message']);
    }

    /**
     * @covers Paradox\Toolbox::normaliseDriverExceptions
     */
    public function testNormaliseDriverExceptionsWithNormalExceptions()
    {
        $exception = new \Exception("some error message", 123456);

        $result = $this->toolbox->normaliseDriverExceptions($exception);
        $this->assertEquals(123456, $result['code']);
        $this->assertEquals("some error message", $result['message']);
    }
}
