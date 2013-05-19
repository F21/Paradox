<?php
namespace tests\Paradox\toolbox;
use tests\Base;
use Paradox\toolbox\GraphManager;
use Paradox\AModel;
use Paradox\pod\Vertex;
use Paradox\toolbox\TransactionManager;

/**
 * Tests for the transaction manager.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class TransactionManagerTest extends Base
{
    /**
     * The collection name for this test case.
     * @var string
     */
    protected $collectionName = 'TransactionManagerTestCollection';

    /**
     * The graph name for this test case.
     * @var string
     */
    protected $graphName = 'TransactionManagerTestGraph';

    /**
     * Stores the transaction manager.
     * @var TransactionManager
     */
    protected $transactionManager;

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

        $this->transactionManager = $this->getTransactionManager();
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
     * Convinence function to get the finder
     */
    protected function getTransactionManager($graph = null)
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $graph);

        return $client->getToolbox()->getTransactionManager();
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $property = $reflectionClass->getProperty('_toolbox');
        $property->setAccessible(true);

        //We need to create an empty object to pass to
        //ReflectionProperty's getValue method
        $manager = new TransactionManager($this->getClient()->getToolbox());

        $this->assertInstanceOf('Paradox\Toolbox', $property->getValue($manager), 'GraphManager constructor did not store a Paradox\Toolbox.');
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::begin
     */
    public function testBegin()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $activeTransactionProperty = $reflectionClass->getProperty('_activeTransaction');
        $activeTransactionProperty->setAccessible(true);

        $transactionPausedProperty = $reflectionClass->getProperty('_transactionPaused');
        $transactionPausedProperty->setAccessible(true);

        //Verify the values before starting the transaction
        $this->assertFalse($activeTransactionProperty->getValue($this->transactionManager), "There should be no active transactions");
        $this->assertTrue($transactionPausedProperty->getValue($this->transactionManager), "The transaction should be marked as paused");

        $this->transactionManager->begin();

        //Verify the values after starting the transaction
        $this->assertTrue($activeTransactionProperty->getValue($this->transactionManager), "There should be an active transactions");
        $this->assertFalse($transactionPausedProperty->getValue($this->transactionManager), "The transaction should not be marked as paused");

        //Try to start another transaction
        try {
            $this->transactionManager->begin();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to start a transaction when there is already an active transaction, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::commit
     */
    public function testCommit()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $activeTransactionProperty = $reflectionClass->getProperty('_activeTransaction');
        $activeTransactionProperty->setAccessible(true);

        $transactionPausedProperty = $reflectionClass->getProperty('_transactionPaused');
        $transactionPausedProperty->setAccessible(true);

        $collectionsProperty = $reflectionClass->getProperty('_collections');
        $collectionsProperty->setAccessible(true);

        $commandsProperty = $reflectionClass->getProperty('_commands');
        $commandsProperty->setAccessible(true);

        $registeredResultsProperty = $reflectionClass->getProperty('_registeredResults');
        $registeredResultsProperty->setAccessible(true);

        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName);
        $manager = $client->getToolbox()->getTransactionManager();

        $manager->begin();

        $vertex1 = $client->dispense("vertex");
        $vertex1->set('name', 'john');
        $client->store($vertex1);
        $manager->registerResult('store1');

        $vertex2 = $client->dispense("vertex");
        $vertex2->set('name', 'john');
        $client->store($vertex2);
        $manager->registerResult('store2');

        $client->delete($vertex1);
        $manager->registerResult('delete');
        $result = $manager->commit();

        $this->assertInternalType('array', $result, "The result should be an array");
        $this->assertNotNull($result['store1'], "Storing should return an id");
        $this->assertNotNull($result['store2'], "Storing should return an id");
        $this->assertTrue($result['delete'], "Deleting should return true");

        //Check to make sure transaction data is reset
        $this->assertFalse($activeTransactionProperty->getValue($manager), "The transaction should not be active");
        $this->assertTrue($transactionPausedProperty->getValue($manager), "The transaction should be paused");
        $this->assertEmpty($collectionsProperty->getValue($manager)['write'], "There should be no collections registered for writing");
        $this->assertEmpty($collectionsProperty->getValue($manager)['read'], "There should be no collections registered for reading");
        $this->assertEmpty($commandsProperty->getValue($manager), "There should be no registered commands");
        $this->assertEmpty($registeredResultsProperty->getValue($manager), "There should be no registered results");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::commit
     */
    public function testCommitWithoutTransaction()
    {
        try {
            $this->transactionManager->commit();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to commit a transaction that does not exist, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::commit
     */
    public function testCommitWithNoCommands()
    {
        $this->transactionManager->begin();

        try {
            $this->transactionManager->commit();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to commit a transaction with no commands, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::cancel
     */
    public function testCancel()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $activeTransactionProperty = $reflectionClass->getProperty('_activeTransaction');
        $activeTransactionProperty->setAccessible(true);

        $transactionPausedProperty = $reflectionClass->getProperty('_transactionPaused');
        $transactionPausedProperty->setAccessible(true);

        $collectionsProperty = $reflectionClass->getProperty('_collections');
        $collectionsProperty->setAccessible(true);

        $commandsProperty = $reflectionClass->getProperty('_commands');
        $commandsProperty->setAccessible(true);

        $registeredResultsProperty = $reflectionClass->getProperty('_registeredResults');
        $registeredResultsProperty->setAccessible(true);

        //Start the transaction and add some data
        $this->transactionManager->begin();
        $this->transactionManager->addReadCollection($this->collectionName);
        $this->transactionManager->addWriteCollection($this->collectionName);
        $this->transactionManager->addCommand('someCommand', 'someManager:someAction');
        $this->transactionManager->registerResult('someResult');
        $this->transactionManager->cancel();

        //Assert
        $this->assertFalse($activeTransactionProperty->getValue($this->transactionManager), "There should not be an active transactions");
        $this->assertTrue($transactionPausedProperty->getValue($this->transactionManager), "The transaction should be marked as paused");
        $this->assertEmpty($collectionsProperty->getValue($this->transactionManager)['write'], "There should be no collections registered for writing");
        $this->assertEmpty($collectionsProperty->getValue($this->transactionManager)['read'], "There should be no collections registered for readomg");
        $this->assertEmpty($commandsProperty->getValue($this->transactionManager), "There should be no registered commands.");
        $this->assertEmpty($registeredResultsProperty->getValue($this->transactionManager), "There should be no registered results.");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::cancel
     */
    public function testCancelWithoutTransaction()
    {
        try {
            $this->transactionManager->cancel();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to cancel a transaction that does not exist, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::clearTransactionInfo
     */
    public function testClearTransactionInfo()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $activeTransactionProperty = $reflectionClass->getProperty('_activeTransaction');
        $activeTransactionProperty->setAccessible(true);

        $transactionPausedProperty = $reflectionClass->getProperty('_transactionPaused');
        $transactionPausedProperty->setAccessible(true);

        $collectionsProperty = $reflectionClass->getProperty('_collections');
        $collectionsProperty->setAccessible(true);

        $commandsProperty = $reflectionClass->getProperty('_commands');
        $commandsProperty->setAccessible(true);

        $registeredResultsProperty = $reflectionClass->getProperty('_registeredResults');
        $registeredResultsProperty->setAccessible(true);

        $clearTransactionInfo = $reflectionClass->getMethod('clearTransactionInfo');
        $clearTransactionInfo->setAccessible(true);

        //Start the transaction and add some data
        $this->transactionManager->begin();
        $this->transactionManager->addReadCollection($this->collectionName);
        $this->transactionManager->addWriteCollection($this->collectionName);
        $this->transactionManager->addCommand('someCommand', 'someManager:someAction');
        $this->transactionManager->registerResult('someResult');
        $clearTransactionInfo->invoke($this->transactionManager);

        //Assert
        $this->assertFalse($activeTransactionProperty->getValue($this->transactionManager), "There should not be an active transactions");
        $this->assertTrue($transactionPausedProperty->getValue($this->transactionManager), "The transaction should be marked as paused");
        $this->assertEmpty($collectionsProperty->getValue($this->transactionManager)['write'], "There should be no collections registered for writing");
        $this->assertEmpty($collectionsProperty->getValue($this->transactionManager)['read'], "There should be no collections registered for readomg");
        $this->assertEmpty($commandsProperty->getValue($this->transactionManager), "There should be no registered commands.");
        $this->assertEmpty($registeredResultsProperty->getValue($this->transactionManager), "There should be no registered results.");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::addReadCollection
     */
    public function testAddReadCollection()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        $collectionsProperty = $reflectionClass->getProperty('_collections');
        $collectionsProperty->setAccessible(true);

        $this->transactionManager->begin();
        $this->transactionManager->addReadCollection($this->collectionName);

        $this->assertContains($this->collectionName, $collectionsProperty->getValue($this->transactionManager)['read'], "The collection was not registered");
        $this->assertCount(1, $collectionsProperty->getValue($this->transactionManager)['read'], "The collection should only be registered once");

        //Add the collection again
        $this->transactionManager->addReadCollection($this->collectionName);
        $this->assertCount(1, $collectionsProperty->getValue($this->transactionManager)['read'], "The collection should only be registered once");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::addReadCollection
     */
    public function testAddReadCollectionWithoutTransaction()
    {

        try {
            $this->transactionManager->addReadCollection($this->collectionName);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to add a collection for reading without an active transaction, but no exception was thrown");

    }

    /**
     * @covers Paradox\toolbox\TransactionManager::addWriteCollection
     */
    public function testAddWriteCollection()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        $collectionsProperty = $reflectionClass->getProperty('_collections');
        $collectionsProperty->setAccessible(true);

        $this->transactionManager->begin();
        $this->transactionManager->addWriteCollection($this->collectionName);

        $this->assertContains($this->collectionName, $collectionsProperty->getValue($this->transactionManager)['write'], "The collection was not registered");
        $this->assertCount(1, $collectionsProperty->getValue($this->transactionManager)['write'], "The collection should only be registered once");

        //Add the collection again
        $this->transactionManager->addWriteCollection($this->collectionName);
        $this->assertCount(1, $collectionsProperty->getValue($this->transactionManager)['write'], "The collection should only be registered once");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::addWriteCollection
     */
    public function testAddWriteCollectionWithoutTransaction()
    {

        try {
            $this->transactionManager->addWriteCollection($this->collectionName);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to add a collection for writing without an active transaction, but no exception was thrown");

    }

    /**
     * @covers Paradox\toolbox\TransactionManager::registerResult
     */
    public function testRegisterResult()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        $registeredResultsProperty = $reflectionClass->getProperty('_registeredResults');
        $registeredResultsProperty->setAccessible(true);

        $object = $model = $this->getMock('Paradox\AModel');

        $this->transactionManager->begin();
        $id = $this->transactionManager->addCommand('somecommand', 'someManager:someAction', $object, true, array('data' => 'test'));
        $this->transactionManager->registerResult('result');

        $this->assertNotNull($id, "The generated id for the command should not be null");
        $this->arrayHasKey($id, $registeredResultsProperty->getValue($this->transactionManager), "The generated id was not in the registered results array");
        $this->assertEquals('result', $registeredResultsProperty->getValue($this->transactionManager)[$id], "The registered name does not match");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::registerResult
     */
    public function testRegisterResultWithoutActiveTransaction()
    {
        try {
            $this->transactionManager->registerResult('result');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to register a result without an active transaction, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::registerResult
     */
    public function testRegisterResultWithoutCommands()
    {
        $this->transactionManager->begin();

        try {
            $this->transactionManager->registerResult('result');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to register a result without any commands, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::executeTransaction
     */
    public function testExecuteTransaction()
    {
        $action = "function(param){return param;}";
        $result = $this->transactionManager->executeTransaction($action, array($this->collectionName), array($this->collectionName), array('1', '2'));

        $this->assertInternalType('array', $result, "The returned result should be an array");
        $this->assertCount(2, $result, "The result should only contain 2 result items");

        foreach ($result as $resultItem) {
            $this->assertContains($resultItem, array('1', '2'), "Unexpected result item");
        }
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::executeTransaction
     */
    public function testExecuteTransactionWithInvalidAction()
    {
        $action = "function(param){bad function}";

        try {
            $result = $this->transactionManager->executeTransaction($action, array($this->collectionName), array($this->collectionName), array('1', '2'));
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to execute a transaction with an invalid action, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::pause
     */
    public function testPause()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        $registeredResultsProperty = $reflectionClass->getProperty('_transactionPaused');
        $registeredResultsProperty->setAccessible(true);

        $this->transactionManager->begin();

        //Verify that the transaction is not paused
        $this->assertFalse($registeredResultsProperty->getValue($this->transactionManager), "The transaction should not be marked as paused");

        $this->transactionManager->pause();

        //Verify that the transaction is paused
        $this->assertTrue($registeredResultsProperty->getValue($this->transactionManager), "The transaction should be marked as paused");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::pause
     */
    public function testPauseWithoutActiveTransaction()
    {
        try {
            $this->transactionManager->pause();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to pause a transaction that is not active, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::pause
     */
    public function testPauseWithPausedTransaction()
    {
        $this->transactionManager->begin();
        $this->transactionManager->pause();

        try {
            $this->transactionManager->pause();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to pause a transaction that is already paused, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::resume
     */
    public function testResume()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        $registeredResultsProperty = $reflectionClass->getProperty('_transactionPaused');
        $registeredResultsProperty->setAccessible(true);

        $this->transactionManager->begin();
        $this->transactionManager->pause();

        //Verify that the transaction is paused
        $this->assertTrue($registeredResultsProperty->getValue($this->transactionManager), "The transaction should be marked as paused");

        $this->transactionManager->resume();

        //Verify that the transaction is not paused
        $this->assertFalse($registeredResultsProperty->getValue($this->transactionManager), "The transaction should not be marked as paused");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::resume
     */
    public function testResumeWithoutActiveTransaction()
    {
        try {
            $this->transactionManager->resume();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to resume a transaction that is not active, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::resume
     */
    public function testResumeWithoutPausedTransaction()
    {
        $this->transactionManager->begin();

        try {
            $this->transactionManager->resume();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to resume an active transaction, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::addCommand
     */
    public function testAddCommand()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        $commandsProperty = $reflectionClass->getProperty('_commands');
        $commandsProperty->setAccessible(true);

        $object = $model = $this->getMock('Paradox\AModel');

        $this->transactionManager->begin();
        $id = $this->transactionManager->addCommand('somecommand', 'someManager:someAction', $object, true, array('data' => 'test'));

        $commands = $commandsProperty->getValue($this->transactionManager);

        $this->assertInternalType('array', $commands, "The commands property should be an array");
        $this->assertNotNull($id, "Adding a command should return a valid id");

        $this->assertArrayHasKey($id, $commands, "The commands property does not have a key matching the id ($id)");
        $this->assertEquals('somecommand', $commands[$id]['command'], "The added command does not match");
        $this->assertEquals('someManager:someAction', $commands[$id]['action'], "The added action does not match");
        $this->assertEquals($object, $commands[$id]['object'], "The added object does not match");
        $this->assertTrue($commands[$id]['isGraph'], "isGraph should be true");
        $this->assertEquals(array('data' => 'test'), $commands[$id]['data'], "The added datadoes not match");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::addCommand
     */
    public function testAddCommandWithoutTransaction()
    {
        $object = $model = $this->getMock('Paradox\AModel');

        try {
            $id = $this->transactionManager->addCommand('somecommand', 'someManager:someAction', $object, true, array('data' => 'test'));
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to add a command without an active transaction, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::processResult
     */
    public function testProcessResult()
    {
        $client = $this->getClient();
        $manager = $client->getToolbox()->getTransactionManager();

        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $processResult = $reflectionClass->getMethod('processResult');
        $processResult->setAccessible(true);

        $manager->begin();

        //Generate the commands
        $document = $client->dispense($this->collectionName);

        $storeId = $manager->addCommand('someCommand', "PodManager:store", $document);
        $manager->registerResult('store');

        $deleteId = $manager->addCommand('someCommand', "PodManager:delete", $document);
        $manager->registerResult('delete');

        $loadId = $manager->addCommand('someCommand', "PodManager:load", null, false, array('type' => $this->collectionName));
        $manager->registerResult('load1');

        $loadId2 = $manager->addCommand('someCommand', "PodManager:load", null, false, array('type' => $this->collectionName));
        $manager->registerResult('load2');

        $getOne = $manager->addCommand('someCommand', "Query:getOne");
        $manager->registerResult('getOne');

        $getAll = $manager->addCommand('someCommand', "Query:getAll");
        $manager->registerResult('getAll');

        $find = $manager->addCommand('someCommand', "Finder:find", null, false, array('type' => $this->collectionName));
        $manager->registerResult('find');

        $findAll = $manager->addCommand('someCommand', "Finder:findAll", null, false, array('type' => $this->collectionName));
        $manager->registerResult('findAll');

        $findNear = $manager->addCommand('someCommand', "Finder:findAll", null, false, array('type' => $this->collectionName, 'coordinates' => array('latitude' => 48, 'longitude' => 48, 'podId' => 'mycollection/123456')));
        $manager->registerResult('findNear');

        $findAllNear = $manager->addCommand('someCommand', "Finder:findAllNear", null, false, array('type' => $this->collectionName, 'coordinates' => array('latitude' => 48, 'longitude' => 48, 'podId' => 'mycollection/123456')));
        $manager->registerResult('findAllNear');

        $findWithin = $manager->addCommand('someCommand', "Finder:findWithin", null, false, array('type' => $this->collectionName, 'coordinates' => array('latitude' => 48, 'longitude' => 48, 'podId' => 'mycollection/123456')));
        $manager->registerResult('findWithin');

        $findAllWithin = $manager->addCommand('someCommand', "Finder:findAllWithin", null, false, array('type' => $this->collectionName, 'coordinates' => array('latitude' => 48, 'longitude' => 48, 'podId' => 'mycollection/123456')));
        $manager->registerResult('findAllWithin');

        $search = $manager->addCommand('someCommand', "Finder:search", null, false, array('type' => $this->collectionName));
        $manager->registerResult('search');

        $searchAll = $manager->addCommand('someCommand', "Finder:searchAll", null, false, array('type' => $this->collectionName));
        $manager->registerResult('searchAll');

        $findOne = $manager->addCommand('someCommand', "Finder:findOne", null, false, array('type' => $this->collectionName));
        $manager->registerResult('findOne');

        $findOneNone = $manager->addCommand('someCommand', "Finder:findOne", null, false, array('type' => $this->collectionName));
        $manager->registerResult('findOneNone');

        $any = $manager->addCommand('someCommand', "Finder:any", null, false, array('type' => $this->collectionName));
        $manager->registerResult('any');

        $findOneNear = $manager->addCommand('someCommand', "Finder:findOneNear", null, false, array('type' => $this->collectionName, 'coordinates' => array('latitude' => 48, 'longitude' => 48, 'podId' => 'mycollection/123456')));
        $manager->registerResult('findOneNear');

        $findOneWithin = $manager->addCommand('someCommand', "Finder:findOneWithin", null, false, array('type' => $this->collectionName, 'coordinates' => array('latitude' => 48, 'longitude' => 48, 'podId' => 'mycollection/123456')));
        $manager->registerResult('findOneWithin');

        $searchForOne = $manager->addCommand('someCommand', "Finder:searchForOne", null, false, array('type' => $this->collectionName));
        $manager->registerResult('searchForOne');

        $getInboundEdges = $manager->addCommand('someCommand', "GraphManager:getInboundEdges");
        $manager->registerResult('getInboundEdges');

        $getOutboundEdges = $manager->addCommand('someCommand', "GraphManager:getOutboundEdges");
        $manager->registerResult('getOutboundEdges');

        $getEdges = $manager->addCommand('someCommand', "GraphManager:getEdges");
        $manager->registerResult('getEdges');

        $getNeighbours = $manager->addCommand('someCommand', "GraphManager:getNeighbours");
        $manager->registerResult('getNeighbours');

        //Generate the result data
        $result = array(
            $storeId => array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1'),
            $deleteId => true,
            $loadId => array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1', 'name' => 'john smith'),
            $loadId2 => null,
            $getOne => array('test' => 'test'),
            $getAll => array(array('test' => 'test1'), array('test' => 'test2')),
            $find => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1')),
            $findAll => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1')),
            $findNear => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1', '_paradox_distance_parameter' => 100)),
            $findAllNear => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1', '_paradox_distance_parameter' => 100)),
            $findWithin => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1', '_paradox_distance_parameter' => 100)),
            $findAllWithin => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1', '_paradox_distance_parameter' => 100)),
            $search => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1')),
            $searchAll => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1')),
            $findOne => array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1'),
            $findOneNone => null,
            $any => array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1'),
            $findOneNear => array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1', '_paradox_distance_parameter' => 100),
            $findOneWithin => array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1', '_paradox_distance_parameter' => 100),
            $searchForOne => array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1'),
            $getInboundEdges => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1')),
            $getOutboundEdges => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1')),
            $getEdges => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1')),
            $getNeighbours => array(array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1')),
        );

        $result = $processResult->invoke($manager, $result);

        //Assert the right results
        $this->assertInternalType('string', $result['store'], "Storing shoudl return the id in a string");
        $this->assertTrue($result['delete'], "Deleting should return true");
        $this->assertInstanceOf('Paradox\AModel', $result['load1'], 'Loading should return a Paradox\AModel');
        $this->assertNull($result['load2'], "Loading a non-existent document should return null");
        $this->assertInternalType('array', $result['getOne'], "Using getOne() on a query should return an array");
        $this->assertInternalType('array', $result['getAll'], "Using getAll() on a query should return an array");
        $this->assertInternalType('array', $result['find'], "find() should return an array");
        $this->assertInternalType('array', $result['findAll'], "findAll() should return an array");
        $this->assertInternalType('array', $result['findNear'], "findNear() should return an array");
        $this->assertInternalType('array', $result['findAllNear'], "findAllNear() should return an array");
        $this->assertInternalType('array', $result['findWithin'], "findWithin() should return an array");
        $this->assertInternalType('array', $result['findAllWithin'], "findAllWithin() should return an array");
        $this->assertInternalType('array', $result['search'], "search() should return an array");
        $this->assertInternalType('array', $result['searchAll'], "searchAll() should return an array");
        $this->assertInstanceOf('Paradox\AModel', $result['findOne'], 'findOne should return a Paradox\AModel');
        $this->assertNull($result['findOneNone'], "If findOne() finds nothing, null should be returned");
        $this->assertInstanceOf('Paradox\AModel', $result['any'], 'any() should a Paradox\AModel');
        $this->assertInstanceOf('Paradox\AModel', $result['findOneNear'], 'findOneNear() should return a Paradox\AModel');
        $this->assertInstanceOf('Paradox\AModel', $result['findOneWithin'], 'findOneWithin() should return a Paradox\AModel');
        $this->assertInstanceOf('Paradox\AModel', $result['searchForOne'], 'searchForOne should return a Paradox\AModel');
        $this->assertInternalType('array', $result['getInboundEdges'], "getInboundEdges() should return an array");
        $this->assertInternalType('array', $result['getOutboundEdges'], "getOutboundEdges() should return an array");
        $this->assertInternalType('array', $result['getEdges'], "getEdges() should return an array");
        $this->assertInternalType('array', $result['getNeighbours'], "getNeighbours() should return an array");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::processResult
     */
    public function testProcessResultWithUnimplementedCommandAction()
    {
        $client = $this->getClient();
        $manager = $client->getToolbox()->getTransactionManager();

        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $processResult = $reflectionClass->getMethod('processResult');
        $processResult->setAccessible(true);

        $manager->begin();

        $invalid = $manager->addCommand('someCommand', "someunimplementedAction");

        //Generate the result data
        $result = array(
                $invalid => array('_id' => 'mycollection/123456', '_key' => '123456', '_rev' => 'rev1'),
        );


        try {
            $result = $processResult->invoke($manager, $result);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\TransactionManagerException', $e, 'Exception thrown was not a Paradox\exceptions\TransactionManagerException');

            return;
        }

        $this->fail("Tried to process a command with an invalid or unimplemented action, but no exception was thrown");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::searchCommandsByActionAndObject
     */
    public function testSearchCommandsByActionAndObject()
    {
        $object1 = $model = $this->getMock('Paradox\AModel');
        $object2 = $model = $this->getMock('Paradox\AModel');
        $object3 = $model = $this->getMock('Paradox\AModel');

        $this->transactionManager->begin();
        $id1 = $this->transactionManager->addCommand('somecommand', 'someManager:someAction', $object1, true, array('data' => 'test'));
        $id2 = $this->transactionManager->addCommand('somecommand', 'someManager:someAction', $object2, true, array('data' => 'test'));
        $id3 = $this->transactionManager->addCommand('somecommand', 'someManager:someAction', $object3, true, array('data' => 'test'));

        $result = $this->transactionManager->searchCommandsByActionAndObject('someManager:someAction', $object2);

        $this->assertInternalType('array', $result, "The result should be an array");
        $this->assertArrayHasKey('id', $result, 'The result should contain an "id" key');
        $this->assertArrayHasKey('position', $result, 'The result should contain an "position" key');

        $this->assertEquals($id2, $result['id'], "The id does not match");
        $this->assertEquals(1, $result['position'], "The position should be 1, since the command is the first one");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::searchCommandsByActionAndObject
     */
    public function testSearchCommandsByActionAndObjectWithNullResult()
    {
        $object1 = $model = $this->getMock('Paradox\AModel');
        $object2 = $model = $this->getMock('Paradox\AModel');

        $this->transactionManager->begin();
        $id = $this->transactionManager->addCommand('somecommand', 'someManager:someAction', $object1, true, array('data' => 'test'));

        $result = $this->transactionManager->searchCommandsByActionAndObject('someManager:anotherAction', $object2);

        $this->assertNull($result, "The result should be null");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::random
     */
    public function testRandom()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\TransactionManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $random = $reflectionClass->getMethod('random');
        $random->setAccessible(true);

        $commands = $reflectionClass->getProperty('_commands');
        $commands->setAccessible(true);

        $generated1 = $random->invoke($this->transactionManager);
        $this->assertNotNull($generated1, "The generated name should not be null");

        $commands->setValue($this->transactionManager, array($generated1 => array('command' => 'somecommand', 'action' => 'someAction')));

        $generated2 = $random->invoke($this->transactionManager);

        $this->assertNotEquals($generated1, $generated2, "The 2 generated ids should not be equal");
    }

    /**
     * @covers Paradox\toolbox\TransactionManager::hasTransaction
     */
    public function testHasTransaction()
    {
        $this->assertFalse($this->transactionManager->hasTransaction(), "There should be no active or unpaused transactions");

        $this->transactionManager->begin();
        $this->assertTrue($this->transactionManager->hasTransaction(), "There should be an active or unpaused transactions");

        $this->transactionManager->pause();
        $this->assertFalse($this->transactionManager->hasTransaction(), "There should be no active or unpaused transactions");

        $this->transactionManager->resume();
        $this->assertTrue($this->transactionManager->hasTransaction(), "There should be an active or unpaused transactions");
    }
}
