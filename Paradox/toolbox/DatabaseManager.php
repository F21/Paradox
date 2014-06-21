<?php
namespace Paradox\toolbox;
use Paradox\Toolbox;
use Paradox\exceptions\DatabaseManagerException;
use triagens\ArangoDb\Database;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Database manager
 * Manages databases, for example, creating and deleting databases.
 *
 * @version 2.1.0
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class DatabaseManager
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
     * Create a database using the current connection's settings and add it as a connection.
     * @param  string                $name The name of the database.
     * @throws DatabaseManagerException
     * @return boolean
     */
    public function createDatabase($name)
    {
        try {
            Database::create($this->getConnection(), $name);

            return true;

        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new DatabaseManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Delete a database.
     * @param  string                $name The name of the database.
     * @throws DatabaseManagerException
     */
    public function deleteDatabase($name)
    {
        try {
            Database::delete($this->getConnection(), $name);

            return true;
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new DatabaseManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Get information about a database.
     * @param  string                $name The name of the database.
     * @throws DatabaseManagerException
     * @return array
     */
    public function getDatabaseInfo($name)
    {
        try {
        	$connection = $this->getConnection($name);

            $info = Database::getInfo($connection);

            $result = array();

            $result['id'] = $info['result']['id'];
            $result['name'] = $info['result']['name'];
            $result['path'] = $info['result']['path'];
            $result['isSystem'] = $info['result']['isSystem'];

            return $result;

        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new DatabaseManagerException($normalised['message'], $normalised['code']);
        }
    }
    
    /**
     * Lists all databases availiable.
     * @throws DatabaseManagerException
     * @return array
     */
    public function listDatabases(){
    	try {
    		$connection = $this->getConnection();
    		 
    		$result = Database::listDatabases($connection);
    	
    		if(empty($result['result'])){
    			return array();
    		}else{
    			return $result['result'];
    		}

    	} catch (\Exception $e) {
    		$normalised = $this->_toolbox->normaliseDriverExceptions($e);
    		throw new DatabaseManagerException($normalised['message'], $normalised['code']);
    	}
    }
    
    /**
     * Get a cloned connection with targetting a database.
     * @param string $database The optional name of the database. Defaults to _system.
     * @return \triagens\ArangoDb\Connection
     */
    private function getConnection($database = '_system'){
    	$connection = clone $this->_toolbox->getConnection();
    	$connection->setDatabase($database);
    	
    	return $connection;
    }
}
