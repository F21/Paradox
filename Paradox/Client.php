<?php
namespace Paradox;
use Paradox\exceptions\ClientException;
use triagens\ArangoDb\Graph;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Paradox client
 * Provides an entry point to the library. For most users, the client should provide almost everything they need.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Client
{
    /**
     * An array of toolboxes which contain tools to work with connections to the database.
     * @var array
     */
    private $_toolboxes = array();

    /**
     * The current connection that will be used if you use any of the client functions.
     * @var string
     */
    private $_currentConnection = null;

    /**
     * Flag to store whether the client is in debug mode or not.
     * @var boolean
     */
    private $_debug;

    /**
     * Store an instance of the model formatter, which is used to load user defined models.
     * @var IModelFormatter
     */
    private $_modelFormatter;

    /**
     * Instantiates the client with a set of connection credentials. The connection will have the name 'default'.
     * @param string $endpoint The endpoint to the server, for example tcp://localhost:8529
     * @param string $username The username to use for the connection.
     * @param string $password The password to use for the connection.
     * @param string $graph    The name of the graph, if you want the connection to work on a graph. For connections working on standard collections/documents, you don't need this.
     */
    public function __construct($endpoint, $username, $password, $graph = null)
    {
        $this->_debug = new Debug(false);
        $this->_modelFormatter = new DefaultModelFormatter();
        $this->addConnection('default', $endpoint, $username, $password, $graph);
        $this->useConnection('default');
    }

    /**
     * Add a connection to the client
     * @param string $name     The name of the connection.
     * @param string $endpoint The endpoint to the server, for example tcp://localhost:8529.
     * @param string $username The username to use for the connection.
     * @param string $password The password to use for the connection.
     * @param string $graph    The name of the graph, if you want the connection to work on a graph. For connections working on standard collections/documents, you don't need this.
     */
    public function addConnection($name, $endpoint, $username, $password, $graph = null)
    {
        $this->_toolboxes[$name] = new Toolbox($endpoint, $username, $password, $graph, $this->_debug, $this->_modelFormatter);
    }

    /**
     * Selects the connection as current connection. Any method executed through the client will use this connection.
     * @param  string          $name The name of the connection to use.
     * @throws ClientException
     */
    public function useConnection($name)
    {
        if (!array_key_exists($name, $this->_toolboxes)) {
            throw new ClientException("The connection ($name) you are trying to use is not registered with the client.");
        }

        $this->_currentConnection = $name;
    }

    /**
     * Get the current connection.
     * @return string
     */
    public function getCurrentConnection()
    {
        return $this->_currentConnection;
    }

    /**
     * Get a toolbox for a connection.
     * @param  string          $name The name of the connection.
     * @throws ClientException
     * @return Toolbox
     */
    public function getToolbox($name = 'default')
    {
        if (!array_key_exists($name, $this->_toolboxes)) {
            throw new ClientException("The toolbox for connection ($name) does not exist! Is the connection added to the client?");
        }

        return $this->_toolboxes[$name];
    }

    /**
     * Adds a model formatter to the client. Once the the model formatter is added, it will be used to determine what models to load in all future operations.
     * @param IModelFormatter $formatter An instance of the formatter.
     */
    public function setModelFormatter(IModelFormatter $formatter)
    {
        $this->_modelFormatter = $formatter;

        foreach ($this->_toolboxes as $toolbox) {
            $toolbox->setModelFormatter($formatter);
        }
    }

    /**
     * Sets up a pod enclosed in a model.
     * @param  string $collection The type of pod you want to dispense. For graphs, only "vertex" and "edge" is allowed. For documents, the name of the collection.
     * @param  string $label      If you are dispensing an edge, you can optionally provide a label for that edge.
     * @return AModel
     */
    public function dispense($collection, $label = null)
    {
        return $this->getToolbox($this->_currentConnection)->getPodManager()->dispense($collection, $label);
    }

    /**
     * Persists a model to the server.
     * @param  AModel $model
     * @return int    The id of the saved pod.
     */
    public function store(AModel $model)
    {
        return $this->getToolbox($this->_currentConnection)->getPodManager()->store($model);
    }

    /**
     * Deletes a model from the server.
     * @param  AModel  $model
     * @return boolean
     */
    public function delete(AModel $model)
    {
        return $this->getToolbox($this->_currentConnection)->getPodManager()->delete($model);
    }

    /**
     * Loads a model from the server using the collection type and the id.
     * @param  string $collection The collection to load from. For graphs, this can only be "vertex" or "edge".
     * @param  string $id         The id (key in ArangoDB jargon) of the document, edge or vertex.
     * @return AModel
     */
    public function load($collection, $id)
    {
        return $this->getToolbox($this->_currentConnection)->getPodManager()->load($collection, $id);
    }

    /**
     * Executes an AQL query and return all results. If nothing is found, an empty array is returned.
     * @param  string $query      The AQL query to run.
     * @param  array  $parameters An optional associative array containing parameters to bind to the query.
     * @return array
     */
    public function getAll($query, array $parameters = array())
    {
        return $this->getToolbox($this->_currentConnection)->getQuery()->getAll($query, $parameters);
    }

    /**
     * Executes an AQL query and return the first result. If nothing is found, null is returned.
     * @param  string $query      The AQL query to run.
     * @param  array  $parameters An optional associative array containing parameters to bind to the query.
     * @return return AModel
     */
    public function getOne($query, array $parameters = array())
    {
        return $this->getToolbox($this->_currentConnection)->getQuery()->getOne($query, $parameters);
    }

    /**
     * Returns the execution plan for a query. This will not execute the query.
     * @param  string $query      The AQL query to run.
     * @param  array  $parameters An optional associative array containing parameters to bind to the query.
     * @return array
     */
    public function explain($query, array $parameters = array())
    {
        return $this->getToolbox($this->_currentConnection)->getQuery()->explain($query, $parameters);
    }

    /**
     * Find documents/edges/vertices by filtering, sorting and limiting them. If nothing is found, an empty array is returned.
     * By default, the placeholder is "doc", so your query could look something like: doc.age > 20 SORT doc.name LIMIT 10
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function find($type, $aql, $params = array(), $placeholder = "doc")
    {
        return $this->getToolbox($this->_currentConnection)->getFinder()->find($type, $aql, $params, $placeholder);
    }

    /**
     * Find documents/edges/vertices by sorting and limiting them. If nothing is found, an empty array is returned.
     * There is no FILTER keyword here so your query could look something like: SORT doc.name LIMIT 10
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function findAll($type, $aql = "", $params = array(), $placeholder = "doc")
    {
        return $this->getToolbox($this->_currentConnection)->getFinder()->findAll($type, $aql, $params, $placeholder);
    }

    /**
     * Return the first documents/edge/vertex by filter, sorting and limiting a collection. If nothing is found, a null is returned.
     * A LIMIT 1 is automatically set, so you do not need to set the LIMIT yourself: doc.name = "john"
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return AModel
     */
    public function findOne($type, $aql, $params = array(), $placeholder = "doc")
    {
        return $this->getToolbox($this->_currentConnection)->getFinder()->findOne($type, $aql, $params, $placeholder);
    }

    /**
     * Create a graph using the current connection's settings and add it as a connection.
     * A connection is automatically added to the client using the name of the graph, so you need to be careful that it does not overwrite
     * another connection with the same name.
     *
     * You do not need to set the vertex and edge collections. They are automatically named for you.
     *
     * For example:
     * $client->createGraph('myGraph');
     * $client->useConnection('myGraph'); //You can now query the graph
     * @param  string  $name The name of the graph.
     * @return boolean
     */
    public function createGraph($name)
    {
        $toolbox = $this->getToolbox($this->_currentConnection);
        $toolbox->getGraphManager()->createGraph($name);
        $this->addConnection($name, $toolbox->getEndpoint(), $toolbox->getUsername(), $toolbox->getPassword(), $name);

        return true;
    }

    /**
     * Get information about a graph.
     * @param  string                $name The name of the graph.
     * @throws GraphManagerException
     * @return array
     */
    public function getGraphInfo($name)
    {
        return $this->getToolbox($this->_currentConnection)->getGraphManager()->getGraphInfo($name);
    }

    /**
     * Delete a graph.
     * @param string $name The name of the graph.
     */
    public function deleteGraph($name)
    {
        return $this->getToolbox($this->_currentConnection)->getGraphManager()->deleteGraph($name);
    }

    /**
     * Create a collection.
     * @param string $name The name of the collection to create.
     */
    public function createCollection($name)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->createCollection($name);
    }

    /**
     * Delete a collection.
     * @param string $name The name of the collection to delete.
     */
    public function deleteCollection($name)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->deleteCollection($name);
    }

    /**
     * Rename a collection.
     * @param string $collection The collection we wish to rename.
     * @param string $newName    The new name of the collection.
     */
    public function renameCollection($collection, $newName)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->renameCollection($collection, $newName);
    }

    /**
     * Deletes all the documents inside the collection, but leaves the indexes and metadata intact.
     * @param string $collection The name of the collection.
     */
    public function wipe($collection)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->wipe($collection);
    }

    /**
     * Get information about the collection.
     * @param  string $collection The name of the collection
     * @return array
     */
    public function getCollectionInfo($collection)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->getCollectionInfo($collection);
    }

    /**
     * Get statistics from a collection.
     * @param  string $collection The name of the collection.
     * @return array
     */
    public function getCollectionStatistics($collection)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->getCollectionStatistics($collection);
    }

    /**
     * Counts the number of documents in a collection.
     * @param  string  $collection The collection.
     * @return integer
     */
    public function count($collection)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->count($collection);
    }

    /**
     * List all the collections on the server.
     * @param  boolean $excludeSystem Whether we want to include system collections in the list or not.
     * @param  boolean $includeInfo   Whether we want to include information about each collection. If false, only a list of collection names will be returned.
     * @return array
     */
    public function listCollections($excludeSystem = true, $includeInfo = false)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->listCollections($excludeSystem, $includeInfo);
    }

    /**
     * Load a collection on the server.
     * @param string $collection The name of the collection.
     */
    public function loadCollection($collection)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->loadCollection($collection);
    }

    /**
     * Unload a collection on the server.
     * @param string $collection The name of the collection.
     */
    public function unloadCollection($collection)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->unloadCollection($collection);
    }

    /**
     * List the indices on a collection.
     * @param string  $collection  The name of the collection.
     * @param boolean $includeInfo Whether we want information on each index. If false, only an array of index ids will be returned.
     */
    public function listIndices($collection, $includeInfo = false)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->listIndices($collection, $includeInfo);
    }

    /**
     * Create a cap constraint on a collection.
     * @param string $collection The name of the collection.
     * @param int    $size       The size of the cap constraint.
     * @link http://www.arangodb.org/manuals/current/IndexCapHttp.html
     * @return int Id of the index created.
     */
    public function createCapConstraint($collection, $size)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->createCapConstraint($collection, $size);
    }

    /**
     * Create a geo index on a collection.
     * @param string       $collection The name of the collection.
     * @param array|string $fields     An array of 2 fields representing the latitude and longitude, or 1 field representing a list attribute.
     * @param boolean      $geoJson    Whether to use geoJson or not.
     * @param boolean      $constraint Whether this is a constraint or not.
     * @param boolean      $ignoreNull Whether to ignore null.
     * @link http://www.arangodb.org/manuals/current/IndexGeoHttp.html
     * @return int Id of the index created.
     */
    public function createGeoIndex($collection, $fields, $geoJson = null, $constraint = null, $ignoreNull = null)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->createGeoIndex($collection, $fields, $geoJson, $constraint, $ignoreNull);
    }

    /**
     * Create a hash index on a collection.
     * @param string       $collection The name of the collection.
     * @param array|string $fields     The array of fields or a string representing 1 field.
     * @param boolean      $unique     Whether the values in the index should be unique or not.
     * @link http://www.arangodb.org/manuals/current/IndexHashHttp.html
     * @return int Id of the index created.
     */
    public function createHashIndex($collection, $fields, $unique = null)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->createHashIndex($collection, $fields, $unique);
    }

    /**
     * Create a fulltext index on a collection.
     * @param string $collection The name of the collection.
     * @param string $field      The field to index. Fulltext indices can currently only index one field.
     * @param int    $minLength  The minimum length of words to index.
     * @link http://www.arangodb.org/manuals/current/IndexFulltextHttp.html
     * @return int Id of the index created.
     */
    public function createFulltextIndex($collection, $field, $minLength = null)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->createFulltextIndex($collection, $field, $minLength);
    }

    /**
     * Create a skip-list index on a collection.
     * @param string       $collection The name of the collection.
     * @param array|string $fields     The array of fields or a string representing 1 field.
     * @param bool         $unique     Whether the index is unique or not.
     * @link http://www.arangodb.org/manuals/current/IndexSkiplistHttp.html
     * @return int Id of the index created.
     */
    public function createSkipListIndex($collection, $fields, $unique = null)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->createSkipListIndex($collection, $fields, $unique);
    }

    /**
     * Delete an index.
     * @param string $collection The collection the index is on.
     * @param string $indexId    The id of the index we want to delete.
     */
    public function deleteIndex($collection, $indexId)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->deleteIndex($collection, $indexId);
    }

    /**
     * Get information about an index.
     * @param string $collection The name of the collection.
     * @param string $indexId    The id of the index.
     */
    public function getIndexInfo($collection, $indexId)
    {
        return $this->getToolbox($this->_currentConnection)->getCollectionManager()->getIndexInfo($collection, $indexId);
    }

    /**
     * Returns a random document from a collection.
     * @param  string $type The collection we want to get the document from.
     * @return AModel
     */
    public function any($collection)
    {
        return $this->getToolbox($this->_currentConnection)->getFinder()->any($collection);
    }

    /**
     * Find documents/edges/vertices near a reference point. If nothing is found, an empty array is returned.
     * There is no FILTER keyword here so your query could look something like: SORT doc.name LIMIT 10
     * @param  string          $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  float           $latitude    The latitude in degrees.
     * @param  float           $longitude   The longitude in degrees.
     * @param  string          $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array           $params      An optional associative array containing parameters to bind to the query.
     * @param  integer         $limit       The maximum number of pods to find.
     * @param  string          $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @throws FinderException
     * @return array
     */
    public function findNear($type, $latitude, $longitude, $aql, $params = array(), $limit = 100, $placeholder = "doc")
    {
        $reference = array('latitude' => $latitude, 'longitude' => $longitude);

        return $this->getToolbox($this->_currentConnection)->getFinder()->findNear($type, $reference, $aql, $params, $limit, $placeholder);
    }

    /**
     * Find documents/edges/vertices near a reference point. If nothing is found, an empty array is returned.
     * There is no FILTER keyword here so your query could look something like: SORT doc.name LIMIT 10
     * @param  string          $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  float           $latitude    The latitude in degrees.
     * @param  float           $longitude   The longitude in degrees.
     * @param  string          $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array           $params      An optional associative array containing parameters to bind to the query.
     * @param  integer         $limit       The maximum number of pods to find.
     * @param  string          $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @throws FinderException
     * @return array
     */
    public function findAllNear($type, $latitude, $longitude, $aql = "", $params = array(), $limit = 100, $placeholder = "doc")
    {
        $reference = array('latitude' => $latitude, 'longitude' => $longitude);

        return $this->getToolbox($this->_currentConnection)->getFinder()->findAllNear($type, $reference, $aql, $params, $limit, $placeholder);
    }

    /**
     * Find one document/edge/vertice near a reference point. If nothing is found, null is returned
     * A LIMIT 1 is automatically set, so you do not need to set the LIMIT yourself: doc.age > 20 SORT doc.name
     * @param  string          $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  float           $latitude    The latitude in degrees.
     * @param  float           $longitude   The longitude in degrees.
     * @param  string          $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array           $params      An optional associative array containing parameters to bind to the query.
     * @param  string          $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @throws FinderException
     * @return null|AModel
     */
    public function findOneNear($type, $latitude, $longitude, $aql, $params = array(), $placeholder = "doc")
    {
        $reference = array('latitude' => $latitude, 'longitude' => $longitude);

        return $this->getToolbox($this->_currentConnection)->getFinder()->findOneNear($type, $reference, $aql, $params, $placeholder);
    }

    /**
     * Find documents/edges/vertices within a radius around the reference point. If nothing is found, an empty array is returned.
     * By default, the placeholder is "doc", so your query could look something like: doc.age > 20 SORT doc.name LIMIT 10
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  float  $latitude    The latitude in degrees.
     * @param  float  $longitude   The longitude in degrees.
     * @param  float  $radius      The radius in meters.
     * @param  string $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function findWithin($type, $latitude, $longitude, $radius, $aql, $params = array(), $placeholder = "doc")
    {
        $reference = array('latitude' => $latitude, 'longitude' => $longitude);

        return $this->getToolbox($this->_currentConnection)->getFinder()->findWithin($type, $reference, $radius, $aql, $params, $placeholder);
    }

    /**
     * Find documents/edges/vertices within a radius around the reference point. If nothing is found, an empty array is returned.
     * There is no FILTER keyword here so your query could look something like: SORT doc.name LIMIT 10
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  float  $latitude    The latitude in degrees.
     * @param  float  $longitude   The longitude in degrees.
     * @param  float  $radius      The radius in meters.
     * @param  string $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function findAllWithin($type, $latitude, $longitude, $radius, $aql = "", $params = array(), $placeholder = "doc")
    {
        $reference = array('latitude' => $latitude, 'longitude' => $longitude);

        return $this->getToolbox($this->_currentConnection)->getFinder()->findAllWithin($type, $reference, $radius, $aql, $params, $placeholder);
    }

    /**
     * Find one document/edge/verticeswithin a radius around the reference point. If nothing is found, null is returned.
     * A LIMIT 1 is automatically set, so you do not need to set the LIMIT yourself: doc.age > 20 SORT doc.name
     * @param  string      $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  float       $latitude    The latitude in degrees.
     * @param  float       $longitude   The longitude in degrees.
     * @param  float       $radius      The radius in meters.
     * @param  string      $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array       $params      An optional associative array containing parameters to bind to the query.
     * @param  string      $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return null|AModel
     */
    public function findOneWithin($type, $latitude, $longitude, $radius, $aql, $params = array(), $placeholder = "doc")
    {
        $reference = array('latitude' => $latitude, 'longitude' => $longitude);

        return $this->getToolbox($this->_currentConnection)->getFinder()->findOneWithin($type, $reference, $radius, $aql, $params, $placeholder);
    }

    /**
     * Search for documents/vertices/edges using a full-text search on an attribute of the documents with filtering. If no results are found, an empty array is returned.
     * By default, the placeholder is "doc", so your query could look something like: doc.age > 20 SORT doc.name LIMIT 10
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string $attribute   The attribute to search on.
     * @param  string $query       The full-text query.
     * @param  string $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function search($type, $attribute, $query, $aql, $params = array(), $placeholder = "doc")
    {
        return $this->getToolbox($this->_currentConnection)->getFinder()->search($type, $attribute, $query, $aql, $params, $placeholder);
    }

    /**
     * Search for documents/vertices/edges using a full-text search on an attribute of the documents without filtering. If no results are found, an empty array is returned.
     * There is no FILTER keyword here so your query could look something like: SORT doc.name LIMIT 10
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string $attribute   The attribute to search on.
     * @param  string $query       The full-text query.
     * @param  string $aql         An optional AQL fragment that will be inserted after the FOR clause.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function searchAll($type, $attribute, $query, $aql = "", $params = array(), $placeholder = "doc")
    {
        return $this->getToolbox($this->_currentConnection)->getFinder()->searchAll($type, $attribute, $query, $aql, $params, $placeholder);
    }

    /**
     * Search for one document/vertex/edge using a full-text search on an attribute of the documents with filtering. If no results are found, null is returned.
     * A LIMIT 1 is automatically set, so you do not need to set the LIMIT yourself: doc.name = "john"
     * @param  string      $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string      $attribute   The attribute to search on.
     * @param  string      $query       The full-text query.
     * @param  string      $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array       $params      An optional associative array containing parameters to bind to the query.
     * @param  string      $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return null|AModel
     */
    public function searchForOne($type, $attribute, $query, $aql, $params = array(), $placeholder = "doc")
    {
        return $this->getToolbox($this->_currentConnection)->getFinder()->searchForOne($type, $attribute, $query, $aql, $params, $placeholder);
    }

    /**
     * Create a user on the server.
     * @param  string  $username The username.
     * @param  string  $password The password
     * @param  boolean $active   Whether this user should be enabled or not
     * @param  array   $data     An optional associative array containing extra data for the user.
     * @return boolean
     */
    public function createUser($username, $password = null, $active = true, $data = array())
    {
        return $this->getToolbox($this->_currentConnection)->getServer()->createUser($username, $password, $active, $data);
    }

    /**
     * Delete a user from the server.
     * @param  string  $username The user we want to delete.
     * @return boolean
     */
    public function deleteUser($username)
    {
        return $this->getToolbox($this->_currentConnection)->getServer()->deleteUser($username);
    }

    /**
     * Get information about a user.
     * @param  string $username The user we want information about.
     * @return array
     */
    public function getUserInfo($username)
    {
        return $this->getToolbox($this->_currentConnection)->getServer()->getUserInfo($username);
    }

    /**
     * Change the password of a user.
     * @param  string  $username The user we want to change the password for.
     * @param  string  $password The new password.
     * @return boolean
     */
    public function changePassword($username, $password)
    {
        return $this->getToolbox($this->_currentConnection)->getServer()->changePassword($username, $password);
    }

    /**
     * Enable or disable the user.
     * @param  string  $username The user to enable or disable.
     * @param  boolean $active   The new enabled or disabled state.
     * @return boolean
     */
    public function setUserActive($username, $active)
    {
        return $this->getToolbox($this->_currentConnection)->getServer()->setUserActive($username, $active);
    }

    /**
     * Update the extra data for a user.
     * @param  string  $username The use we wish to update.
     * @param  array   $data     The associative array containing data we want to update.
     * @return boolean
     */
    public function updateUserData($username, array $data)
    {
        return $this->getToolbox($this->_currentConnection)->getServer()->updateUserData($username, $data);
    }

    /**
     * Get the server version.
     * @return string
     */
    public function getVersion()
    {
        return $this->getToolbox($this->_currentConnection)->getServer()->getVersion();
    }

    /**
     * Get detailed information about the server.
     * @return array
     */
    public function getServerInfo()
    {
        return $this->getToolbox($this->_currentConnection)->getServer()->getServerInfo();
    }

    /**
     * Get the unix timestamp of the server in microseconds.
     * @return integer
     */
    public function getTime()
    {
        return $this->getToolbox($this->_currentConnection)->getServer()->getTime();
    }

    /**
     * Set to true to turn on the debugger. Set it to false to turn it off.
     * @param boolean $value
     */
    public function debug($value)
    {
        $this->_debug->setDebug($value);
    }

}
