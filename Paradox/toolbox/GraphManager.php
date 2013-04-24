<?php
namespace Paradox\toolbox;
use Paradox\Toolbox;
use Paradox\exceptions\GraphManagerException;
use Paradox\AModel;
use Paradox\pod\Document;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Graph manager
 * Manages graphs, for example, creating and deleting graphs.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class GraphManager
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
     * Create a graph using the current connection's settings and add it as a connection.
     * You do not need to set the vertex and edge collections. They are automatically named for you.
     * @param  string          $name The name of the graph.
     * @throws GraphManagerException
     * @return boolean
     */
    public function createGraph($name)
    {
        $graph = new \triagens\ArangoDb\Graph($name);
        $graph->setVerticesCollection($name . 'VertexCollection');
        $graph->setEdgesCollection($name . 'EdgeCollection');

        try {
            return $this->_toolbox->getGraphHandler()->createGraph($graph);

        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new GraphManagerException($normalised['message'], $normalised['code']);
        }

        return true;
    }

    /**
     * Delete a graph.
     * @param  string          $name The name of the graph.
     * @throws GraphManagerException
     */
    public function deleteGraph($name)
    {
        try {
            $graphHandler = $this->_toolbox->getGraphHandler();

            return $graphHandler->dropGraph($name);
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new GraphManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Get all inbound edges to this vertex. The edges can be filtered by their labels and properties.
     * @param Document|Model|string A vertex pod, model or string of the vertex id.
     * @param string $label      A string representing one label or an array of labels we want the inbound edges to have.
     * @param array  $properties An array of property filters. Each filter is an associative array with the following properties:
     *                              'key' - Filter the result vertices by a key value pair.
     *                              'value' -  The value of the key.
     *                              'compare' - A comparison operator. (==, >, <, >=, <= )
     * @return array
     */
    public function getInboundEdges($model, $label = null, array $properties = null)
    {
        $id = $this->getVertexId($model);

        if (!$id) {
            return array();
        }

        $filters = $this->generateFilter("in", $label, $properties);

        $edges = $this->_toolbox->getGraphHandler()->getConnectedEdges($this->_toolbox->getGraph(), $id, $filters);
        $result = $edges->getAll();

        if (empty($result)) {
            return array();
        }

        foreach ($result as &$edge) {
            $edge = $this->convertDocumentToEdge($edge);
        }

        return $this->_toolbox->getPodManager()->convertToPods("edge", $result);
    }

    /**
     * Get all outbound edges to this vertex. The edges can be filtered by their labels and properties.
     * @param Document|Model|string A vertex pod, model or string of the vertex id.
     * @param string $label      A string representing one label or an array of labels we want the inbound edges to have.
     * @param array  $properties An array of property filters. Each filter is an associative array with the following properties:
     *                              'key' - Filter the result vertices by a key value pair.
     *                              'value' -  The value of the key.
     *                              'compare' - A comparison operator. (==, >, <, >=, <= )
     * @return array
     */
    public function getOutboundEdges($model, $label = null, array $properties = null)
    {
           $id = $this->getVertexId($model);

        if (!$id) {
            return array();
        }

        $filters = $this->generateFilter("out", $label, $properties);

        $edges = $this->_toolbox->getGraphHandler()->getConnectedEdges($this->_toolbox->getGraph(), $id, $filters);
        $result = $edges->getAll();

        if (empty($result)) {
            return array();
        }

        foreach ($result as &$edge) {
            $edge = $this->convertDocumentToEdge($edge);
        }

        return $this->_toolbox->getPodManager()->convertToPods("edge", $result);
    }

    /**
     * Get all edges connected to this vertex. The edges can be filtered by their labels and properties.
     * @param Document|Model|string A vertex pod, model or string of the vertex id.
     * @param string $label      A string representing one label or an array of labels we want the inbound edges to have.
     * @param array  $properties An array of property filters. Each filter is an associative array with the following properties:
     *                              'key' - Filter the result vertices by a key value pair.
     *                              'value' -  The value of the key.
     *                              'compare' - A comparison operator. (==, >, <, >=, <= )
     * @return array
     */
    public function getEdges($model, $label = null, array $properties = null)
    {
        $id = $this->getVertexId($model);

        if (!$id) {
            return array();
        }

        $filters = $this->generateFilter(null, $label, $properties);

        $edges = $this->_toolbox->getGraphHandler()->getConnectedEdges($this->_toolbox->getGraph(), $id, $filters);
        $result = $edges->getAll();

        if (empty($result)) {
            return array();
        }

        foreach ($result as &$edge) {
            $edge = $this->convertDocumentToEdge($edge);
        }

        return $this->_toolbox->getPodManager()->convertToPods("edge", $result);
    }

    /**
     * Get the neighbour vertices connected to this vertex via some edge.
     * @param Document|AModel|string A vertex pod, model or string of the vertex id.
     * @param string $direction  "in" for inbound neighbours, "out" for outbound neighbours and "any" for all neighbours.
     * @param string $label      A string representing one label or an array of labels we want the inbound edges to have.
     * @param string $properties An array of property filters. Each filter is an associative array with the following properties:
     *                              'key' - Filter the result vertices by a key value pair.
     *                              'value' -  The value of the key.
     *                              'compare' - A comparison operator. (==, >, <, >=, <= )
     * @return array
     */
    public function getNeighbours($model, $direction = "both", $label = null, array $properties = null)
    {
           $id = $this->getVertexId($model);

        if (!$id) {
            return array();
        }

        $filters = $this->generateFilter($direction, $label, $properties);

        $vertices = $this->_toolbox->getGraphHandler()->getNeighborVertices($this->_toolbox->getGraph(), $id, $filters);
        $result = $vertices->getAll();

        if (empty($result)) {
            return array();
        }

        foreach ($result as &$vertex) {
            $vertex = $this->convertDocumentToVertex($vertex);
        }

        return $this->_toolbox->getPodManager()->convertToPods("vertex", $result);
    }

    /**
     * Convinence function that gets the document id (handle) from models, vertex pods and strings.
     * @param  Document|AModel|string $model
     * @throws GraphManagerException
     */
    protected function getVertexId($model)
    {
        if ($model instanceof AModel) {
            return $model->getPod()->getId();
        } elseif ($model instanceof Document) {
            return $model->getId();
        } elseif (is_string($model)) {
            return $model;
        } else {
            throw new GraphManagerException('$model can be either a model, a vertex pod or the id of the vertex.');
        }
    }

    /**
     * Function to generate the array of filters that will be sent to the server.
     * @param string $direction  The direction we wish to filter: "any", "in", "out".
     * @param string $label      A string representing one label or an array of labels we want the inbound edges to have.
     * @param string $properties An array of property filters. Each filter is an associative array with the following properties:
     *                              'key' - Filter the result vertices by a key value pair.
     *                              'value' -  The value of the key.
     *                              'compare' - A comparison operator. (==, >, <, >=, <= )
     * @return array
     */
    protected function generateFilter($direction = null, $labels = null, $properties = null)
    {
        $filter = array();

        if ($direction) {
            $filter['direction'] = $direction;
        }

        if ($labels) {

            if (is_string($labels)) {
                $labels = array($labels);
            }

            $filter['labels'] = $labels;
        }

        if ($properties) {
            $filter['properties'] = $properties;
        }

        return array('filter' => $filter);
    }

    /**
     * Converts an ArangoDB-PHP document object to an ArangoDB-PHP edge object. This is needed because ArangoDB-PHP
     * returns document objects for queried edges.
     * @param  \triagens\ArangoDb\Document $document The ArangoDB-PHP document object.
     * @return \triagens\ArangoDb\Edge
     */
    protected function convertDocumentToEdge(\triagens\ArangoDb\Document $document)
    {
        $data = $document->getAll(array('_includeInternals' => true));

        return \triagens\ArangoDb\Edge::createFromArray($data);
    }

    /**
     * Converts an ArangoDB-PHP document object to an ArangoDB-PHP vertex object. This is needed because ArangoDB-PHP
     * returns document objects for queried vertices.
     * @param  \triagens\ArangoDb\Document $document The ArangoDB-PHP document object.
     * @return \triagens\ArangoDb\Vertex
     */
    protected function convertDocumentToVertex(\triagens\ArangoDb\Document $document)
    {
        $data = $document->getAll(array('_includeInternals' => true));

        return \triagens\ArangoDb\Vertex::createFromArray($data);
    }
}
