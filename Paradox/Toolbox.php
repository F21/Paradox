<?php
namespace Paradox;
use Paradox\toolbox\Finder;
use Paradox\toolbox\PodManager;
use triagens\ArangoDb\ConnectionOptions;
use triagens\ArangoDb\Connection;
use triagens\ArangoDb\GraphHandler;
use triagens\ArangoDb\DocumentHandler;
use triagens\ArangoDb\CollectionHandler;
use Paradox\toolbox\Query;
use Paradox\exceptions\ToolboxException;
use Paradox\toolbox\Server;
use Paradox\toolbox\GraphManager;
use Paradox\toolbox\CollectionManager;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Toolbox
 * Each toolbox represents a connection. The toolbox acts as a box from which we can get our tools from, from example, the PodManager, Finder, etc.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Toolbox
{
    /**
     * The endpoint or server's address.
     * @var string
     */
    private $_endpoint;

    /**
     * The username used to connect to the server.
     * @var string
     */
    private $_username;

    /**
     * The password used to connect to the server.
     * @var string
     */
    private $_password;

    /**
     * If the connection manages a graph, then the name of the graph is stored here.
     * @var string
     */
    private $_graph;

    /**
     * An instance of the ArangoDB-PHP DocumentHandler or GraphHandler.
     * @var \triagens\ArangoDb\DocumentHandler|\triagens\ArangoDb\GraphHandler
     */
    private $_driver;

    /**
     * An instance of the ArangoDB-PHP Connection.
     * @var \triagens\ArangoDb\Connection
     */
    private $_connection;

    /**
     * An instance of the pod manager.
     * @var PodManager
     */
    private $_podManager;

    /**
     * An instance of the graph manager.
     * @var GraphManager
     */
    private $_graphManager;

    /**
     * An instance of the collection manager.
     * @var CollectionManager
     */
    private $_collectionManager;

    /**
     * An instance of the finder.
     * @var Finder
     */
    private $_finder;

    /**
     * An instance of the query helper.
     * @var Query
     */
    private $_query;

    /**
     * An instance of the server manager.
     * @var Server
     */
    private $_server;

    /**
     * An reference to the debugger.
     * @var Debug
     */
    private $_debug;

    /**
     * An reference to the formatter.
     * @var IModelFormatter
     */
    private $_formatter;

    /**
     * Sets up the toolbox and create and inject any required components.
     * @param string          $endpoint  The endpoint to the server, for example tcp://localhost:8529
     * @param string          $username  The username to use for the connection.
     * @param string          $password  The password to use for the connection.
     * @param string          $graph     The name of the graph, if you want the connection to work on a graph. For connections working on standard collections/documents, you don't need this.
     * @param Debug           $debug     A reference to the debugger.
     * @param IModelFormatter $formatter A reference to the model formatter.
     */
    public function __construct($endpoint, $username = null, $password = null, $graph = null, Debug $debug, IModelFormatter $formatter)
    {
        $this->_endpoint = $endpoint;
        $this->_username = $username;
        $this->_password = $password;
        $this->_graph = $graph;
        $this->_debug = $debug;
        $this->_formatter = $formatter;

        $this->_finder = new Finder($this);
        $this->_podManager = new PodManager($this);
        $this->_collectionManager = new CollectionManager($this);
        $this->_query = new Query($this);
        $this->_server = new Server($this);
        $this->_graphManager = new GraphManager($this);
    }

    /**
     * Get the endpoint.
     * @return string
     */
    public function getEndpoint()
    {
        return $this->_endpoint;
    }

    /**
     * Get the username.
     * @return string
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * Get the password.
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Get the name of the graph if this toolbox manages a graph.
     * @return string
     */
    public function getGraph()
    {
        return $this->_graph;
    }

    /**
     * Whether this toolbox manages a graph.
     * @return boolean
     */
    public function isGraph()
    {
        return (bool) $this->_graph;
    }

    /**
     * Generate a name for the vertex collection if this toolbox manages a graph.
     * @throws ToolboxException
     * @return string
     */
    public function getVertexCollectionName()
    {
        if (!$this->isGraph()) {
            throw new ToolboxException("getVertexCollectionName() can only be used for connections that manages graphs.");
        }

        return $this->getGraph() . 'VertexCollection';
    }

    /**
     * Generate a name for the edge collection if this toolbox manages a graph.
     * @throws ToolboxException
     * @return string
     */
    public function getEdgeCollectionName()
    {
        if (!$this->isGraph()) {
            throw new ToolboxException("getVertexCollectionName() can only be used for connections that manages graphs.");
        }

        return $this->getGraph() . 'EdgeCollection';
    }

    /**
     * Get the pod manager.
     * @return \Paradox\toolbox\PodManager
     */
    public function getPodManager()
    {
        return $this->_podManager;
    }

    /**
     * Get the collection manager.
     * @return \Paradox\toolbox\CollectionManager
     */
    public function getCollectionManager()
    {
        return $this->_collectionManager;
    }

    /**
     * Get the graph manager.
     * @throws ToolboxException
     * @return \Paradox\toolbox\GraphManager
     */
    public function getGraphManager()
    {
        return $this->_graphManager;
    }

    /**
     * Get the finder.
     * @return \Paradox\toolbox\Finder
     */
    public function getFinder()
    {
        return $this->_finder;
    }

    /**
     * Get the query helper.
     * @return \Paradox\toolbox\Query
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Get the server manager.
     * @return \Paradox\toolbox\Server
     */
    public function getServer()
    {
        return $this->_server;
    }

    /**
     * Returns the name of the model we should instantiate given a type.
     * @param string $type The name of the collection. For toolboxes that manages graphs, only "vertex" and "edge" is valid.
     */
    public function formatModel($type)
    {
        return $this->_formatter->formatModel($type, $this->_graph);
    }

    /**
     * Checks to see if the pod belongs to this toolbox.
     * @param AModel|Document $pod The pod to validate.
     */
    public function validatePod($pod)
    {
        if ($pod instanceof  AModel) {
            $pod = $pod->getPod();
        }

        if ($pod->compareToolbox($this)) {
            return true;
        } else {
            throw new ToolboxException("The pod/model does not belong to this toolbox.");
        }
    }

    /**
     * Get the ArangoDB-PHP connection.
     * @return \triagens\ArangoDb\Connection
     */
    public function getConnection()
    {
        if (!$this->_connection) {

            $options = array(
                    // server endpoint to connect to
                    ConnectionOptions::OPTION_ENDPOINT       => $this->_endpoint,
                    // authorization type to use (currently supported: 'Basic')
                    ConnectionOptions::OPTION_AUTH_TYPE      => 'Basic',
                    // user for basic authorization
                    ConnectionOptions::OPTION_AUTH_USER      => $this->_username,
                    // password for basic authorization
                    ConnectionOptions::OPTION_AUTH_PASSWD    => $this->_password,
                    ConnectionOptions::OPTION_TRACE          => $this->_debug,
                    ConnectionOptions::OPTION_ENHANCED_TRACE => true
            );

            $this->_connection = new Connection($options);
        }

        return $this->_connection;
    }

    /**
     * Get the ArangoDB-PHP document handler.
     * @return \triagens\ArangoDb\DocumentHandler
     */
    public function getDocumentHandler()
    {
        return new DocumentHandler($this->getConnection());
    }

    /**
     * Get the ArangoDB-PHP graph handler.
     * @return \triagens\ArangoDb\GraphHandler
     */
    public function getGraphHandler()
    {
        return new GraphHandler($this->getConnection());
    }

    /**
     * Get the ArangoDB-PHP collection handler.
     * @return \triagens\ArangoDb\CollectionHandler
     */
    public function getCollectionHandler()
    {
        return new CollectionHandler($this->getConnection());
    }

    /**
     * Automatically determines which handler we need and return it.
     * @return \triagens\ArangoDb\DocumentHandler|\triagens\ArangoDb\GraphHandler
     */
    public function getDriver()
    {
        if (!$this->_driver) {

            if ($this->_graph) {
                $this->_driver = $this->getGraphHandler();
            } else {
                $this->_driver = $this->getDocumentHandler();
            }
        }

        return $this->_driver;
    }

    /**
     * Given the id in ArangoDB format (mycollection/123456) parse it and return the key (123456).
     * @param  string $id
     * @return string
     */
    public function parseIdForKey($id)
    {
        @list(,$key) = explode('/', $id, 2);

        return $key;
    }

    /**
     * Given the id in ArangoDB format (mycollection/123456) parse it and return an array of the collection and id.
     * @param  string $id
     * @return array
     */
    public function parseId($id)
    {
        @list($collection,$key) = explode('/', $id, 2);

        return array('collection' => $collection, 'key' => $key);
    }
    
    /**
     * Normalises the exceptions thrown by ArangoDB-PHP.
     * @param \Exception $exception
     * @return array
     */
    public function normaliseDriverExceptions(\Exception $exception){
    	
    	if($exception instanceof \triagens\ArangoDb\ServerException){
    		return array('message' => $exception->getServerMessage(), 'code' => $exception->getServerCode());
    	}else{
    		return array('message' => $exception->getMessage(), 'code' => $exception->getCode());
    	}
    }
}
