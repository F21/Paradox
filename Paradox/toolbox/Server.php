<?php
namespace Paradox\toolbox;
use Paradox\Toolbox;
use Paradox\exceptions\ServerException;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Server manager.
 * Manages server tasks, for example, creating and deleting users and retriving server statistics.
 *
 * @version 1.2.3
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
     * @param string $username The username.
     * @param string $password The password
     * @param boolean $active Whether this user should be enabled or not
     * @param array $data An optional associative array containing extra data for the user.
     * @throws ServerException
     * @return boolean
     */
    public function createUser($username, $password = null, $active = true, $data = array()){
    	
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
     * @param string $username The user we want to delete.
     * @throws ServerException
     * @return boolean
     */
    public function deleteUser($username){
    	
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
     * @param string $username The user we want information about.
     * @throws ServerException
     * @return array
     */
    public function getUserInfo($username){
    	try {
    		$details = $this->_toolbox->getUserHandler()->get($username);
    		
    		$result = array();
    		$result['username'] = $details->get('username');
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
     * @param string $username The user we want to change the password for.
     * @param string $password The new password.
     * @return boolean
     */
    public function changePassword($username, $password){
    	
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
     * @param string $username The user to enable or disable.
     * @param boolean $active The new enabled or disabled state.
     * @throws ServerException
     * @return boolean
     */
    public function setUserActive($username, $active){
    	
    	try {
    		$this->_toolbox->getUserHandler()->updateUser($username, null, (bool)$active);
    		return true;
    	} catch (\Exception $e) {
    		$normalised = $this->_toolbox->normaliseDriverExceptions($e);
    		throw new ServerException($normalised['message'], $normalised['code']);
    	}
    }
    
    /**
     * Update the extra data for a user.
     * @param string $username The use we wish to update.
     * @param array $data The associative array containing data we want to update.
     * @throws ServerException
     * @return boolean
     */
    public function updateUserData($username, array $data){
    	try {
    		$this->_toolbox->getUserHandler()->updateUser($username, null, null, $data);
    		return true;
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
    public function getVersion(){
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
    public function getServerInfo(){
    	try {
    		return $this->_toolbox->getAdminHandler()->getServerVersion(true);
    	} catch (\Exception $e) {
    		$normalised = $this->_toolbox->normaliseDriverExceptions($e);
    		throw new ServerException($normalised['message'], $normalised['code']);
    	}
    }
    
    /**
     * Get the unix timestamp of the server in microseconds.
     * @throws ServerException
     * @return integer
     */
    public function getTime(){
    	try {
    		return $this->_toolbox->getAdminHandler()->getServerTime();
    	} catch (\Exception $e) {
    		$normalised = $this->_toolbox->normaliseDriverExceptions($e);
    		throw new ServerException($normalised['message'], $normalised['code']);
    	}
    }
}
