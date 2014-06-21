<?php
namespace tests\Paradox\toolbox;
use tests\Base;
use Paradox\exceptions\DatabaseManagerException;
use Paradox\toolbox\DatabaseManager;

/**
 * Tests for the database manager.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class DatabaseManagerTest extends Base
{
    /**
     * The collection name for this test case.
     * @var string
     */
    protected $databaseName = 'TestDatabase';

    /**
     * Stores the database manager.
     * @var DatabaseManager
     */
    protected $databaseManager;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $client = $this->getClient();

        //Try to delete any leftovers
        try {
            $client->deleteDatabase($this->databaseName);
        } catch (\Exception $e) {
            //Ignore any errors
        }

        $client->createDatabase($this->databaseName);

        $this->databaseManager = $this->getDatabaseManager();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $client = $this->getClient();

        try {
            $client->deleteDatabase($this->databaseName);
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteDatabase($this->databaseName . '2');
        } catch (\Exception $e) {
            //Ignore any errors
        }
    }

    /**
     * Convinience function to get the database manager.
     * @return \Paradox\toolbox\DatabaseManager
     */
    protected function getDatabaseManager()
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), null, $this->databaseName);

        return $client->getToolbox()->getDatabaseManager();
    }

    /**
     * @covers Paradox\toolbox\DatabaseManager::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\DatabaseManager');

        //Then we need to get the property we wish to test
        //and make it accessible
        $property = $reflectionClass->getProperty('_toolbox');
        $property->setAccessible(true);

        //We need to create an empty object to pass to
        //ReflectionProperty's getValue method
        $manager = new DatabaseManager($this->getClient()->getToolbox());

        $this->assertInstanceOf('Paradox\Toolbox', $property->getValue($manager), 'DatabaseManager constructor did not store a Paradox\Toolbox.');
    }

    /**
     * @covers Paradox\toolbox\DatabaseManager::createDatabase
     */
    public function testCreateDatabase()
    {
        $result = $this->databaseManager->createDatabase($this->databaseName . '2');

        $this->assertTrue($result, "There was a problem creating the database.");

        //Try to fetch it from the server
        $info = $this->databaseManager->getDatabaseInfo($this->databaseName . '2');

        $this->assertEquals($this->databaseName . '2', $info['name'], "The retrieved database name does not match the saved one");

        $this->databaseManager->deleteDatabase($this->databaseName . '2');
    }

    /**
     * @covers Paradox\toolbox\DatabaseManager::createDatabase
     */
    public function testCreateInvalidDatabase()
    {
        try {
            $id = $this->databaseManager->createDatabase('!123456');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\DatabaseManagerException', $e, 'Exception thrown was not a Paradox\exceptions\DatabaseManagerException');

            return;
        }

        $this->fail('Creating a database with an invalid name did not throw an exception');
    }

    /**
     * @covers Paradox\toolbox\DatabaseManager::deleteDatabase
     */
    public function testDeleteDatabase()
    {
        //List the databases
        $databases = $this->databaseManager->listDatabases();
        $this->assertContains($this->databaseName, $databases, 'The database does not exist');

        $this->databaseManager->deleteDatabase($this->databaseName);
        $databases = $this->databaseManager->listDatabases();
        $this->assertNotContains($this->databaseName, $databases, 'The database was not deleted successfully');
    }

    /**
     * @covers Paradox\toolbox\DatabaseManager::deleteDatabase
     */
    public function testDeleteInvalidCollection()
    {
        //Try to delete a nonexistent database
        try {
            $this->databaseManager->deleteDatabase('DatabaseThatDoesNotExist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\DatabaseManagerException', $e, 'Exception thrown was not a Paradox\exceptions\DatabaseManagerException');

            return;
        }

        $this->fail('Deleting an invalid database did not throw an exception');
    }

    /**
     * @covers Paradox\toolbox\DatabaseManager::getDatabaseInfo
     */
    public function testGetDatabaseInfo()
    {
        $info = $this->databaseManager->getDatabaseInfo($this->databaseName);
        $this->assertEquals($this->databaseName, $info['name'], "Database info's name does not match the name of the database");
        $this->assertInternalType("string", $info['id'], "Database's id does not exist");
        $this->assertInternalType("string", $info['path'], "Database's path does not exist");
        $this->assertInternalType("boolean", $info['isSystem'], "Database's isSystem flag does not exist");
    }

    /**
     * @covers Paradox\toolbox\DatabaseManager::getDatabaseInfo
     */
    public function testGetDatabaseInfoForInvalidDatabase()
    {
        try {
            $info = $this->databaseManager->getDatabaseInfo('DatabaseThatDoesNotExist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\DatabaseManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\DatabaseManagerException');

            return;
        }

        $this->fail("Tried to get information for an invalid database but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\DatabaseManager::listDatabases
     */
    public function testListDatabases()
    {
        //List user collections in standard form
        $databases = $this->databaseManager->listDatabases();
        $this->assertInternalType('array', $databases, "Listed databases is not an array");
        $this->assertContains($this->databaseName, $databases, "$this->databaseName does not exist in the database list");

        foreach ($databases as $database) {
            $this->assertInternalType('string', $database, "Database name is not a string");
        }
    }

    /**
     * @covers Paradox\toolbox\DatabaseManager::listDatabases
     */
    public function testListDatabasesInvalidServer()
    {
        $client = $this->getClient('tcp://nonexistenthost:8529', $this->getDefaultUsername(), $this->getDefaultPassword());

        try {
            $collections = $client->listDatabases();

        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\DatabaseManagerException', $e, 'Exception thrown was not of the type Paradox\exceptions\DatabaseManagerException');

            return;
        }

        $this->fail("Tried to get the databases of a nonexistent server but an exception was not thrown");
    }

    /**
     * @covers Paradox\toolbox\DatabaseManager::getConnection
     */
    public function testGetConnectionWithNoDatabase()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\DatabaseManager');

        $method = $reflectionClass->getMethod('getConnection');
        $method->setAccessible(true);

        $manager = new DatabaseManager($this->getClient()->getToolbox());

        //With the ids being javascript properties
        $result = $method->invoke($manager);

        $this->assertInstanceOf('triagens\ArangoDb\Connection', $result, 'A triagens\ArangoDb\Connection should be returned');

        $this->assertEquals('_system', $result->getDatabase(), "The default database _system was not used when we requested a connection without specifying a database");
    }

    /**
     * @covers Paradox\toolbox\DatabaseManager::getConnection
     */
    public function testGetConnectionWithDatabase()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\DatabaseManager');

        $method = $reflectionClass->getMethod('getConnection');
        $method->setAccessible(true);

        $manager = new DatabaseManager($this->getClient()->getToolbox());

        //With the ids being javascript properties
        $result = $method->invoke($manager, 'testdatabase');

        $this->assertInstanceOf('triagens\ArangoDb\Connection', $result, 'A triagens\ArangoDb\Connection should be returned');

        $this->assertEquals('testdatabase', $result->getDatabase(), "The database 'testdatabase' was not used when we requested a connection specifying it");
    }
}
