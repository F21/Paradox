<?php
namespace tests\Paradox\toolbox;
use tests\Base;
use Paradox\toolbox\Server;
use Paradox\exceptions\ServerException;

/**
 * Tests for the server manager.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class ServerTest extends Base
{
    /**
     * Holds an instance of the server manager.
     * @var Server
     */
    protected $server;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->server = $this->getServer();
        $this->server->createUser('testuser');

        try {
            $this->server->deleteAQLFunction("paradoxtest::helloworld");
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $this->server->deleteAQLFunction("paradoxtest::helloworld2");
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $this->server->deleteAQLFunction("paradoxtest2::helloworld");
        } catch (\Exception $e) {
            //Ignore the error
        }
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        try {
            $this->getClient()->deleteUser('testuser');
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $this->getClient()->deleteUser('testuser1');
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $this->server->deleteAQLFunction("paradoxtest::helloworld");
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $this->server->deleteAQLFunction("paradoxtest::helloworld2");
        } catch (\Exception $e) {
            //Ignore the error
        }

        try {
            $this->server->deleteAQLFunction("paradoxtest2::helloworld");
        } catch (\Exception $e) {
            //Ignore the error
        }
    }

    /**
     * Convinence function to get the server manager.
     * @return \Paradox\toolbox\Server
     */
    protected function getServer()
    {
        return $this->getClient()->getToolbox()->getServer();
    }

    /**
     * @covers Paradox\toolbox\Server::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\Server');

        //Then we need to get the property we wish to test
        //and make it accessible
        $property = $reflectionClass->getProperty('_toolbox');
        $property->setAccessible(true);

        //We need to create an empty object to pass to
        //ReflectionProperty's getValue method
        $manager = new Server($this->getClient()->getToolbox());

        $this->assertInstanceOf('Paradox\Toolbox', $property->getValue($manager), 'Server constructor did not store a Paradox\Toolbox.');
    }

    /**
     * @covers Paradox\toolbox\Server::createUser
     * @covers Paradox\toolbox\Server::deleteUser
     * @covers Paradox\toolbox\Server::getUserInfo
     */
    public function testCreateUser()
    {
        //Create the user and verify
        $this->server->createUser('testuser1', 'password', true, array('name' => 'john'));

        $user = $this->server->getUserInfo('testuser1');

        $this->assertEquals('testuser1', $user['username'], 'The username does not match');
        $this->assertTrue($user['active'], 'The user is not marked as active');
        $this->assertEquals('john', $user['data']['name'], 'The name does not match');

        //Delete the user and verify
        $this->server->deleteUser('testuser1');

        try {
            $user = $this->server->getUserInfo('testuser1');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to get information about a non-existing user, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::createUser
     */
    public function testCreateUserThatAlreadyExists()
    {
        try {
            $user = $this->server->createUser('root');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to create a user that already exists, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::deleteUser
     */
    public function testDeleteInvalidUser()
    {
        try {
            $user = $this->server->deleteUser('auserthatdoesnotexist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to delete a non-existing user, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::getUserInfo
     */
    public function testGetUserInfoForNonExistentUser()
    {
        try {
            $user = $this->server->getUserInfo('auserthatdoesnotexist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to get info for a non-existing user, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::changePassword
     */
    public function testChangePassword()
    {
        //Change the password
        $this->server->changePassword('testuser', 'password1');

        //Test with the old password
        //TODO: ArangoDB accepts connections from anyone, so we can't test for this yet.
        $oldPasswordClient = $this->getClient($this->getDefaultEndpoint(), 'testuser', 'password');

        $client = $this->getClient($this->getDefaultEndpoint(), 'testuser', 'password1');
    }

    /**
     * @covers Paradox\toolbox\Server::changePassword
     */
    public function testChangePasswordForNonExistentUser()
    {
        try {
            $user = $this->server->changePassword('auserthatdoesnotexist', 'newpassword');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to change password for a non-existing user, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::setUserActive
     */
    public function testSetUserActive()
    {
        //Disable the user
        $this->server->setUserActive('testuser', false);

        //Verify
        $userInfo = $this->server->getUserInfo('testuser');
        $this->assertFalse($userInfo['active'], "The user should not be active");

        //Enable the user
        $this->server->setUserActive('testuser', true);

        //Verify
        $userInfo = $this->server->getUserInfo('testuser');
        $this->assertTrue($userInfo['active'], "The user should be active");
    }

    /**
     * @covers Paradox\toolbox\Server::setUserActive
     */
    public function testSetUserActiveOnNonExistentUser()
    {
        try {
            $user = $this->server->setUserActive('auserthatdoesnotexist', false);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to set active state for a non-existing user, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::updateUserData
     */
    public function testUpdateUserData()
    {
        $this->server->updateUserData('testuser', array('age' => 20));

        //Verify
        $userInfo = $this->server->getUserInfo('testuser');
        $this->assertEquals(20, $userInfo['data']['age'], "The age does not match");
    }

    /**
     * @covers Paradox\toolbox\Server::updateUserData
     */
    public function testUpdateUserDataOnNonExistingUser()
    {
        try {
            $user = $this->server->updateUserData('auserthatdoesnotexist', array('age' => 20));
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to update data for a non-existing user, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::createAQLFunction
     */
    public function testCreateAQLFunction()
    {
        $function = "function () {return 'hello';}";

        $this->server->createAQLFunction("paradoxtest::helloworld", $function);

        $registered = $this->server->listAQLFunctions("paradoxtest");

        $this->assertCount(1, $registered, "There should only be one paradoxtest function");
        $this->assertArrayHasKey("paradoxtest::helloworld", $registered, "The AQL function was not registered");
        $this->assertEquals($function, $registered['paradoxtest::helloworld'], "The AQL function's code does not match");

        $this->server->deleteAQLFunction("paradoxtest::helloworld");
    }

    /**
     * @covers Paradox\toolbox\Server::createAQLFunction
     */
    public function testCreateAQLFunctionWithInvalidNameAndCode()
    {
        $function = "brokencode";

        try {
            $this->server->createAQLFunction("!!!!", $function);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to register a function with invalid name and code, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::deleteAQLFunction
     */
    public function testDeleteAQLFunction()
    {
        $function = "function () {return 'hello';}";

        $this->server->createAQLFunction("paradoxtest::helloworld", $function);

        $registered = $this->server->listAQLFunctions("paradoxtest");

        $this->assertCount(1, $registered, "There should only be one paradoxtest function");
        $this->assertArrayHasKey("paradoxtest::helloworld", $registered, "The AQL function was not registered");
        $this->assertEquals($function, $registered['paradoxtest::helloworld'], "The AQL function's code does not match");

        $this->server->deleteAQLFunction("paradoxtest::helloworld");

        $registered = $this->server->listAQLFunctions("paradoxtest");

        $this->assertEmpty($registered, "There should be no paradoxtest functions registered");
    }

    /**
     * @covers Paradox\toolbox\Server::deleteAQLFunction
     */
    public function testDeleteAQLFunctionThatDoesNotExist()
    {

        try {
            $this->server->deleteAQLFunction('NameThatDefinitelyDoesNotExist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to delete a non-existing AQL function, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::deleteAQLFunctionsByNamespace
     */
    public function testDeleteAQLFunctionsByNamespace()
    {
        $function = "function () {return 'hello';}";

        $this->server->createAQLFunction("paradoxtest::helloworld", $function);
        $this->server->createAQLFunction("paradoxtest::helloworld2", $function);

        $registered = $this->server->listAQLFunctions("paradoxtest");

        $this->assertCount(2, $registered, "There should be 2 paradoxtest functions");
        $this->assertArrayHasKey("paradoxtest::helloworld", $registered, "The AQL function was not registered");
        $this->assertArrayHasKey("paradoxtest::helloworld2", $registered, "The AQL function was not registered");

        $this->server->deleteAQLFunctionsByNamespace("paradoxtest");

        $registered = $this->server->listAQLFunctions("paradoxtest");

        $this->assertEmpty($registered, "There should be no paradoxtest functions registered");
    }

    /**
     * @covers Paradox\toolbox\Server::deleteAQLFunctionsByNamespace
     */
    public function testDeleteAQLFunctionsByNamespaceOnInvalidServer()
    {

        $client = $this->getClient('tcp://nonexistentserver', $this->getDefaultUsername(), $this->getDefaultPassword());

        try {
            $functions = $client->getToolbox()->getServer()->deleteAQLFunctionsByNamespace('paradoxtest');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to delete aql functions in a namespace on a server that does not exist, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::listAQLFunctions
     */
    public function testListAQLFunctions()
    {
        $function = "function () {return 'hello';}";

        $this->server->createAQLFunction("paradoxtest::helloworld", $function);
        $this->server->createAQLFunction("paradoxtest2::helloworld", $function);

        $registeredParadoxtest = $this->server->listAQLFunctions("paradoxtest");

        $this->assertCount(1, $registeredParadoxtest, "There should only be one paradoxtest function");
        $this->assertArrayHasKey("paradoxtest::helloworld", $registeredParadoxtest, "The AQL function was not registered");
        $this->assertEquals($function, $registeredParadoxtest['paradoxtest::helloworld'], "The AQL function's code does not match");

        $registeredParadoxtest2 = $this->server->listAQLFunctions("paradoxtest2");

        $this->assertCount(1, $registeredParadoxtest2, "There should only be one paradoxtest2 function");
        $this->assertArrayHasKey("paradoxtest2::helloworld", $registeredParadoxtest2, "The AQL function was not registered");
        $this->assertEquals($function, $registeredParadoxtest2['paradoxtest2::helloworld'], "The AQL function's code does not match");

        $registeredAll = $this->server->listAQLFunctions();
        $this->assertGreaterThanOrEqual(2, count($registeredAll), "There should be at least 2 registered functions");
        $this->assertArrayHasKey("paradoxtest::helloworld", $registeredAll, "The paradoxtest::helloworld AQL function should be in the list");
        $this->assertArrayHasKey("paradoxtest2::helloworld", $registeredAll, "The paradoxtest2::helloworld AQL function should be in the list");
        $this->assertEquals($function, $registeredAll['paradoxtest::helloworld'], "The AQL function's code does not match");
        $this->assertEquals($function, $registeredAll['paradoxtest2::helloworld'], "The AQL function's code does not match");

        $this->server->deleteAQLFunction("paradoxtest::helloworld");
        $this->server->deleteAQLFunction("paradoxtest2::helloworld");

        $registered = $this->server->listAQLFunctions("paradoxtest");

        $this->assertEmpty($registered, "There should be no paradoxtest functions registered");

        $registered = $this->server->listAQLFunctions("paradoxtest2");

        $this->assertEmpty($registered, "There should be no paradoxtest2 functions registered");
    }

    /**
     * @covers Paradox\toolbox\Server::listAQLFunctions
     */
    public function testListAQLFunctionsOnInvalidServer()
    {
        $client = $this->getClient('tcp://nonexistentserver', $this->getDefaultUsername(), $this->getDefaultPassword());

        try {
            $functions = $client->getToolbox()->getServer()->listAQLFunctions();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to list registered AQL functions on server that does not exist, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::getVersion
     */
    public function testGetVersion()
    {
        $version = $this->server->getVersion();
        $this->assertInternalType('string', $version, "The version should be a string");
    }

    /**
     * @covers Paradox\toolbox\Server::getVersion
     */
    public function testGetVersionOnInvalidServer()
    {
        $client = $this->getClient('tcp://nonexistentserver', $this->getDefaultUsername(), $this->getDefaultPassword());

        try {
            $version = $client->getToolbox()->getServer()->getVersion();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to get version for server that does not exist, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::getServerInfo
     */
    public function testGetServerInfo()
    {
        $info = $this->server->getServerInfo();
        $this->assertInternalType('array', $info, "The server info should be an array");
    }

    /**
     * @covers Paradox\toolbox\Server::getServerInfo
     */
    public function testGetServerInfoOnInvalidServer()
    {
        $client = $this->getClient('tcp://nonexistentserver', $this->getDefaultUsername(), $this->getDefaultPassword());

        try {
            $version = $client->getToolbox()->getServer()->getServerInfo();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to get info for server that does not exist, but an exception was not thrown');
    }

    /**
     * @covers Paradox\toolbox\Server::getTime
     */
    public function testGetTime()
    {
        $time = $this->server->getTime();
        $this->assertInternalType('float', $time, "The server time should be an integer");
    }

    /**
     * @covers Paradox\toolbox\Server::getTime
     */
    public function testGetTimeOnInvalidServer()
    {
        $client = $this->getClient('tcp://nonexistentserver', $this->getDefaultUsername(), $this->getDefaultPassword());

        try {
            $version = $client->getToolbox()->getServer()->getTime();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ServerException', $e, 'Exception thrown was not of the type Paradox\exceptions\ServerException');

            return;
        }

        $this->fail('Tried to get time for server that does not exist, but an exception was not thrown');
    }
}
