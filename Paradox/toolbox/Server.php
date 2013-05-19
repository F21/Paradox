<?php
namespace Paradox\toolbox;
use Paradox\Toolbox;
use Paradox\exceptions\ServerException;
use triagens\ArangoDb\AqlUserFunction;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Server manager.
 * Manages server tasks, for example, creating and deleting users and retriving server statistics.
 *
 * @version 1.3.0
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Server
{
    /**
     * A reference to the toolbox.
     * @var Toolbox
     */
    private $_toolbox;

    /**
     * Instantiates the finder.
     * @param Toolbox $toolbox
     */
    public function __construct(Toolbox $toolbox)
    {
        $this->_toolbox = $toolbox;
    }

    /**
     * Create a user on the server.
     * @param  string          $username The username.
     * @param  string          $password The password
     * @param  boolean         $active   Whether this user should be enabled or not
     * @param  array           $data     An optional associative array containing extra data for the user.
     * @throws ServerException
     * @return boolean
     */
    public function createUser($username, $password = null, $active = true, $data = array())
    {
        try {
            $this->_toolbox->getUserHandler()->addUser($username, $password, $active, $data);

            return true;
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }

    }

    /**
     * Delete a user from the server.
     * @param  string          $username The user we want to delete.
     * @throws ServerException
     * @return boolean
     */
    public function deleteUser($username)
    {
        try {
            $this->_toolbox->getUserHandler()->removeUser($username);

            return true;
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Get information about a user.
     * @param  string          $username The user we want information about.
     * @throws ServerException
     * @return array
     */
    public function getUserInfo($username)
    {
        try {
            $details = $this->_toolbox->getUserHandler()->get($username);

            $result = array();
            $result['username'] = $details->get('user');
            $result['active'] = $details->get('active');
            $result['data'] = $details->get('extra');

            return $result;

        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Change the password of a user.
     * @param  string  $username The user we want to change the password for.
     * @param  string  $password The new password.
     * @return boolean
     */
    public function changePassword($username, $password)
    {
        try {
            $this->_toolbox->getUserHandler()->updateUser($username, $password);

            return true;
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Enable or disable the user.
     * @param  string          $username The user to enable or disable.
     * @param  boolean         $active   The new enabled or disabled state.
     * @throws ServerException
     * @return boolean
     */
    public function setUserActive($username, $active)
    {
        try {
            $this->_toolbox->getUserHandler()->updateUser($username, null, (bool) $active);

            return true;
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Update the extra data for a user.
     * @param  string          $username The use we wish to update.
     * @param  array           $data     The associative array containing data we want to update.
     * @throws ServerException
     * @return boolean
     */
    public function updateUserData($username, array $data)
    {
        try {
            $this->_toolbox->getUserHandler()->updateUser($username, null, null, $data);

            return true;
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Register an AQL function with the server.
     * @param  string          $name The name of the server
     * @param  string          $code The javascript code of the function.
     * @throws ServerException
     */
    public function createAQLFunction($name, $code)
    {
        try {
        	$userFunction = new AqlUserFunction($this->_toolbox->getConnection());
        	$userFunction->setName($name);
        	$userFunction->setCode($code);
            $userFunction->register();
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }

        return true;
    }

    /**
     * Delete an AQL function by its name.
     * @param  string          $name The name of the function to delete.
     * @throws ServerException
     */
    public function deleteAQLFunction($name)
    {
        try {
        	$userFunction = new AqlUserFunction($this->_toolbox->getConnection());
            $userFunction->unregister($name);
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }

        return true;
    }

    /**
     * Delete all the AQL functions within a namespace.
     * @param  string          $namespace The name of the namespace to delete.
     * @throws ServerException
     */
    public function deleteAQLFunctionsByNamespace($namespace)
    {
        try {
        	$userFunction = new AqlUserFunction($this->_toolbox->getConnection());
            $userFunction->unregister($namespace, true);
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }

        return true;
    }

    /**
     * List the AQL functions registered on the server and optionally, filter by namespace.
     * @param  string          $namespace The optional namespace to filter the list of AQL functions on.
     * @throws ServerException
     */
    public function listAQLFunctions($namespace = null)
    {
        try {
            $userFunction = new AqlUserFunction($this->_toolbox->getConnection());
            $functions = $userFunction->getRegisteredUserFunctions($namespace);

            $result = array();

            foreach ($functions as $function) {
                $result[$function['name']] = $function['code'];
            }

            return $result;

        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Get the server version.
     * @throws ServerException
     * @return string
     */
    public function getVersion()
    {
        try {
            return $this->_toolbox->getAdminHandler()->getServerVersion();
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Get detailed information about the server.
     * @throws ServerException
     * @return array
     */
    public function getServerInfo()
    {
        try {
            return $this->_toolbox->getAdminHandler()->getServerVersion(true);
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Get the unix timestamp of the server with microsecond precision
     * @throws ServerException
     * @return float
     */
    public function getTime()
    {
        try {
            return $this->_toolbox->getAdminHandler()->getServerTime();
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new ServerException($normalised['message'], $normalised['code']);
        }
    }
}
