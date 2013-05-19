<?php
namespace Paradox\toolbox;
use Paradox\Toolbox;
use Paradox\pod\Vertex;
use Paradox\exceptions\TransactionManagerException;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Transaction
 * Manages transactions.
 *
 * @version 1.3.0
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class TransactionManager
{
    /**
     * A reference to the toolbox.
     * @var Toolbox
     */
    private $_toolbox;

    /**
     * Whether there is an active transaction or not.
     * @var boolean
     */
    private $_activeTransaction = false;

    /**
     * Whether the transaction is paused.
     * @var boolean
     */
    private $_transactionPaused = true;

    /**
     * An array holding the collections that will be locked and written to or read from in the transaction.
     * @var array
     */
    private $_collections = array('write' => array(), 'read' => array());

    /**
     * A list of commands for the transaction.
     * @var array
     */
    private $_commands = array();

    /**
     * An array of names for which we want results from the transaction to be returned.
     * @var array
     */
    private $_registeredResults = array();

    /**
     * Initiate the transaction manager.
     * @param Toolbox $toolbox
     */
    public function __construct(Toolbox $toolbox)
    {
        $this->_toolbox = $toolbox;
    }

    /**
     * Begin a transaction.
     * @return boolean
     */
    public function begin()
    {
        if ($this->_activeTransaction) {
            throw new TransactionManagerException("An active transaction already exists.");
        }

        $this->_activeTransaction = true;
        $this->_transactionPaused = false;

        return true;
    }

    /**
     * Commit the transaction.
     * @return array
     */
    public function commit()
    {
        if (!$this->_activeTransaction) {
            throw new TransactionManagerException("There is no active transaction to commit.");
        }

        if (empty($this->_commands)) {
            throw new TransactionManagerException("There is no transaction operations to commit.");
        }

        $commandText = 'function () { var db = require("internal").db; ';

        //Check if there are graph operations
        foreach ($this->_commands as $command) {

            if ($command['isGraph']) {
                $commandText .= "var g = require('org/arangodb/graph').Graph; var graph = new g('{$this->_toolbox->getGraph()}'); ";
                break;
            }
        }

        $commandText .= 'var result = {}; ';

        //Now, add the commands
        foreach ($this->_commands as $id => $command) {

            $commandText .= "result.$id = {$command['command']} ";
        }

        $commandText .= "return result; }";

        //Send the transaction
        $result = $this->executeTransaction($commandText, $this->_collections['read'], $this->_collections['write']);

        //Process the result
        $processed = $this->processResult($result);

        $this->clearTransactionInfo();

        return $processed;
    }

    /**
     * Cancel the transaction.
     * @return boolean
     */
    public function cancel()
    {
        if (!$this->_activeTransaction) {
            throw new TransactionManagerException("There is no active transaction to cancel.");
        }

        $this->clearTransactionInfo();

        return true;
    }

    /**
     * Clear the saved information for the transaction.
     */
    private function clearTransactionInfo()
    {
        $this->_activeTransaction = false;
        $this->_transactionPaused = true;
        $this->_collections = array('write' => array(), 'read' => array());
        $this->_commands = array();
        $this->_registeredResults = array();
    }

    /**
     * Add a collection that will be locked for reading.
     * @param string $collection The name of the collection.
     */
    public function addReadCollection($collection)
    {
        if (!$this->_activeTransaction) {
            throw new TransactionManagerException("There is no active transaction.");
        }

        if (!in_array($collection, $this->_collections['read'])) {
            $this->_collections['read'][] = $collection;
        }
    }

    /**
     * Add a collection that will be locked for writing.
     * @param string $collection The name of the collection.
     */
    public function addWriteCollection($collection)
    {
        if (!$this->_activeTransaction) {
            throw new TransactionManagerException("There is no active transaction.");
        }

        if (!in_array($collection, $this->_collections['write'])) {
            $this->_collections['write'][] = $collection;
        }
    }

    /**
     * Register the result of a transaction operation under a name, so that the result can be retrieved after the transaction has finished.
     * @param  string  $name    The name to store the results under.
     * @param  string  $command A placebo that does nothing, but allows one to explicitly associate a registration with a transaction operation.
     * @return boolean
     */
    public function registerResult($name, $command = null)
    {
        if (!$this->_activeTransaction) {
            throw new TransactionManagerException("There is no active transaction.");
        }

        if (empty($this->_commands)) {
            throw new TransactionManagerException("There are no commands for this transaction.");
        }

        //Get the last command's id (current array element)
        end($this->_commands);
        $id = key($this->_commands);
        reset($this->_commands);

        $this->_registeredResults[$id] = $name;

        return true;
    }

    /**
     * Send a raw transaction to the server and return the result.
     * @param  string                      $action           The javascript function for the transaction.
     * @param  array                       $readCollections  An array of collections to be locked for reading.
     * @param  array                       $writeCollections An array of collections to be locked for writing.
     * @param  array                       $parameters       An array of parameters for executing the transaction multiple times.
     * @throws TransactionManagerException
     * @return mixed
     */
    public function executeTransaction($action, $readCollections = array(), $writeCollections = array(), $parameters = array())
    {
        $transaction = $this->_toolbox->getTransactionObject();
        $transaction->setAction($action);
        $transaction->setReadCollections($readCollections);
        $transaction->setWriteCollections($writeCollections);

        if (!empty($parameters)) {
            $transaction->setParams($parameters);
        }

        //Send
        try {
            $result = $transaction->execute();

            return $result;
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new TransactionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Pause the transaction, so that operations after this point are not part of the transaction.
     */
    public function pause()
    {
        if (!$this->_activeTransaction) {
            throw new TransactionManagerException("There is no active transaction.");
        }

        if ($this->_transactionPaused) {
            throw new TransactionManagerException("The transaction is already paused.");
        }

        $this->_transactionPaused = true;
    }

    /**
     * Resume the transaction, so that operations after this point are part of the transaction.
     */
    public function resume()
    {
        if (!$this->_activeTransaction) {
            throw new TransactionManagerException("There is no active transaction.");
        }

        if (!$this->_transactionPaused) {
            throw new TransactionManagerException("The transaction is not paused.");
        }

        $this->_transactionPaused = false;
    }

    /**
     * Add a command to be executed for the transaction.
     * @param  string $command The javascript command for the transaction.
     * @param  string $action  The name of the operation.
     * @param  string $object  An optional object or model that can be used to process results after committing the transaction.
     * @param  string $isGraph Whether this command is for a graph or not.
     * @param  array  $data    Any other data that should be stored with this command.
     * @return string $id The id of this command.
     */
    public function addCommand($command, $action, $object = null, $isGraph = false, $data = array())
    {
        if (!$this->_activeTransaction) {
            throw new TransactionManagerException("There is no active transaction.");
        }

        $id = $this->random();
        $this->_commands[$id] = array('command' => $command, 'action' => $action, 'object' => $object, 'isGraph' => $isGraph, 'data' => $data);

        return $id;
    }

    /**
     * Processes the transaction result after committing.
     * @param  array                       $results The results array from the server.
     * @throws TransactionManagerException
     * @return array
     */
    private function processResult($results)
    {
        $processedResults = array();

        foreach ($results as $id => $result) {

            $command = $this->_commands[$id];

            switch ($command['action']) {

                case "PodManager:store":
                    $processed = $this->_toolbox->getPodManager()->processStoreResult($command['object']->getPod(), $result['_rev'], $result['_id']);
                    break;

                case "PodManager:delete":
                    $this->_toolbox->getPodManager()->processDeleteResult($command['object']->getPod());
                    $processed = true;
                    break;

                case "PodManager:load":

                    $parsedId = $this->_toolbox->parseId($result['_id']);

                    if (!$result) {
                        $processed = null;
                        break;
                    }

                    $processed = $this->_toolbox->getPodManager()->convertArrayToPod($command['data']['type'], $result);
                    break;

                case "Query:getOne":
                case "Query:getAll":
                    $processed = $result;
                    break;

                case "Finder:find":
                case "Finder:findAll":
                case "Finder:findNear":
                case "Finder:findAllNear":
                case "Finder:findWithin":
                case "Finder:findAllWithin":
                case "Finder:search":
                case "Finder:searchAll":
                    if (isset($command['data']['coordinates'])) {
                        $processed = $this->_toolbox->getFinder()->convertToPods($command['data']['type'], $result, $command['data']['coordinates']);
                    } else {
                        $processed = $this->_toolbox->getFinder()->convertToPods($command['data']['type'], $result);
                    }

                    break;

                case "Finder:findOne":
                case "Finder:any":
                case "Finder:findOneNear":
                case "Finder:findOneWithin":
                case "Finder:searchForOne":

                    if ($result == null) {
                        $processed = null;
                        break;
                    }

                    if (isset($command['data']['coordinates'])) {
                        $processed = $this->_toolbox->getFinder()->convertToPods($command['data']['type'], array($result), $command['data']['coordinates']);
                    } else {
                        $processed = $this->_toolbox->getFinder()->convertToPods($command['data']['type'], array($result));
                    }

                    $processed = reset($processed);

                    break;

                case "GraphManager:getInboundEdges":
                case "GraphManager:getOutboundEdges":
                case "GraphManager:getEdges":
                    $processed = $this->_toolbox->getGraphManager()->convertToPods("edge", $result);
                    break;

                case "GraphManager:getNeighbours":
                    $processed = $this->_toolbox->getGraphManager()->convertToPods("vertex", $result);
                    break;

                default:
                    throw new TransactionManagerException("Invalid or unimplemented action ({$command['action']}) while processing the transaction results.");
            }

            if (array_key_exists($id, $this->_registeredResults)) {
                $processedResults[$this->_registeredResults[$id]] = $processed;
            }
        }

        return $processedResults;
    }

    /**
     * Search the commands for an action and its object. If a match is found, the position and id is returned.
     * The search starts in reverse direction.
     * @param  string $action The action to search on
     * @param  mixed  $object The object to match.
     * @return array
     */
    public function searchCommandsByActionAndObject($action, $object)
    {
        $position = 0;
        $length = count($this->_commands);
        foreach (array_reverse($this->_commands) as $id => $command) {

            if ($command['action'] == $action && $command['object'] === $object) {
                return array('position' => $length - 1 - $position, 'id' => $id);
            }

            $position++;
        }

        return null;
    }

    /**
     * Convinence function to generate a random name for each command that will be sent to the server.
     * @return string
     */
    private function random()
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';

        $id = '';

        while (strlen($id) < 7 || in_array($id, array_keys($this->_commands))) {
            $id .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $id;
    }
    
    /**
     * Whether a transaction has been started or not.
     * @return boolean
     */
    public function transactionStarted(){
    	return $this->_activeTransaction;
    }

    /**
     * Returns whether there is an active transaction that has not been paused.
     * @return boolean
     */
    public function hasTransaction()
    {
        return $this->_activeTransaction && !$this->_transactionPaused;
    }
}
