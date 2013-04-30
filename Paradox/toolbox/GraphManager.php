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
     * @param  string                $name The name of the graph.
     * @throws GraphManagerException
     * @return boolean
     */
    public function createGraph($name)
    {
        try {
            $graph = new \triagens\ArangoDb\Graph($name);
            $graph->setVerticesCollection($name . 'VertexCollection');
            $graph->setEdgesCollection($name . 'EdgeCollection');

            return $this->_toolbox->getGraphHandler()->createGraph($graph);

        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new GraphManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Delete a graph.
     * @param  string                $name The name of the graph.
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
     * Get information about a graph.
     * @param  string                $name The name of the graph.
     * @throws GraphManagerException
     * @return array
     */
    public function getGraphInfo($name)
    {
        try {
            $graphHandler = $this->_toolbox->getGraphHandler();

            $graph = $graphHandler->getGraph($name);

            $result = array();

            $result['id'] = $graph->getKey();
            $result['name'] = $graph->getKey();
            $result['revision'] = $graph->getRevision();
            $result['verticesCollection'] = $graph->getVerticesCollection();
            $result['edgesCollection'] = $graph->getEdgesCollection();

            return $result;

        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new GraphManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Get all inbound edges to this vertex. The edges can be filtered by their labels and AQL.
     * @param Document|Model|string A vertex pod, model or string of the vertex id.
     * @param  string $label       A string representing one label or an array of labels we want the inbound edges to have.
     * @param  string $aql         An optional AQL fragment if we want to filter the edges, for example: FILTER doc.someproperty == "somevalue"
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function getInboundEdges($model, $label = null, $aql = "", $params = array(), $placeholder = "doc")
    {
        $id = $this->getVertexId($model);

        if (!$id) {
            return array();
        }

        $collectionParameter = $this->_toolbox->generateBindingParameter('@collection', $params);
        $vertexParameter = $this->_toolbox->generateBindingParameter('vertexid', $params);
        $directionParameter = $this->_toolbox->generateBindingParameter('direction', $params);

        if (!$label) {
            $query = "FOR $placeholder in EDGES(@$collectionParameter, @$vertexParameter, @$directionParameter) " . $aql . " return $placeholder";
        } else {
            $labelParameter = $this->_toolbox->generateBindingParameter('label', $params);
            $params[$labelParameter] = $label;
            $query = "FOR $placeholder in EDGES(@$collectionParameter, @$vertexParameter, @$directionParameter, [{'\$label': @$labelParameter}]) " . $aql . " return $placeholder";
        }

        $params[$collectionParameter] = $this->_toolbox->getEdgeCollectionName();
        $params[$vertexParameter] = $id;
        $params[$directionParameter] = "inbound";

        try {
            $result = $this->_toolbox->getQuery()->getAll($query, $params);
        } catch (\Exception $e) {
            throw new GraphManagerException($e->getMessage(), $e->getCode());
        }

        if (empty($result)) {
            return array();
        }

        return $this->convertToPods("edge", $result);
    }

    /**
     * Get all outbound edges to this vertex. The edges can be filtered by their labels and AQL.
     * @param Document|Model|string A vertex pod, model or string of the vertex id.
     * @param  string $label       A string representing one label or an array of labels we want the inbound edges to have.
     * @param  string $aql         An optional AQL fragment if we want to filter the edges, for example: FILTER doc.someproperty == "somevalue"
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function getOutboundEdges($model, $label = null, $aql = "", $params = array(), $placeholder = "doc")
    {
        $id = $this->getVertexId($model);

        if (!$id) {
            return array();
        }

        $collectionParameter = $this->_toolbox->generateBindingParameter('@collection', $params);
        $vertexParameter = $this->_toolbox->generateBindingParameter('vertexid', $params);
        $directionParameter = $this->_toolbox->generateBindingParameter('direction', $params);

        if (!$label) {
            $query = "FOR $placeholder in EDGES(@$collectionParameter, @$vertexParameter, @$directionParameter) " . $aql . " return $placeholder";
        } else {
            $labelParameter = $this->_toolbox->generateBindingParameter('label', $params);
            $params[$labelParameter] = $label;
            $query = "FOR $placeholder in EDGES(@$collectionParameter, @$vertexParameter, @$directionParameter, [{'\$label': @$labelParameter}]) " . $aql . " return $placeholder";
        }

        $params[$collectionParameter] = $this->_toolbox->getEdgeCollectionName();
        $params[$vertexParameter] = $id;
        $params[$directionParameter] = "outbound";

        try {
            $result = $this->_toolbox->getQuery()->getAll($query, $params);
        } catch (\Exception $e) {
            throw new GraphManagerException($e->getMessage(), $e->getCode());
        }

        if (empty($result)) {
            return array();
        }

        return $this->convertToPods("edge", $result);
    }

    /**
     * Get all edges connected to this vertex. The edges can be filtered by their labels and AQL.
     * @param Document|Model|string A vertex pod, model or string of the vertex id.
     * @param  string $label       A string representing one label or an array of labels we want the inbound edges to have.
     * @param  string $aql         An optional AQL fragment if we want to filter the edges, for example: FILTER doc.someproperty == "somevalue"
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function getEdges($model, $label = null, $aql = "", $params = array(), $placeholder = "doc")
    {
        $id = $this->getVertexId($model);

        if (!$id) {
            return array();
        }

        $collectionParameter = $this->_toolbox->generateBindingParameter('@collection', $params);
        $vertexParameter = $this->_toolbox->generateBindingParameter('vertexid', $params);
        $directionParameter = $this->_toolbox->generateBindingParameter('direction', $params);

        if (!$label) {
            $query = "FOR $placeholder in EDGES(@$collectionParameter, @$vertexParameter, @$directionParameter) " . $aql . " return $placeholder";
        } else {
            $labelParameter = $this->_toolbox->generateBindingParameter('label', $params);
            $params[$labelParameter] = $label;
            $query = "FOR $placeholder in EDGES(@$collectionParameter, @$vertexParameter, @$directionParameter, [{'\$label': @$labelParameter}]) " . $aql . " return $placeholder";
        }

        $params[$collectionParameter] = $this->_toolbox->getEdgeCollectionName();
        $params[$vertexParameter] = $id;
        $params[$directionParameter] = "any";

        try {
            $result = $this->_toolbox->getQuery()->getAll($query, $params);
        } catch (\Exception $e) {
            throw new GraphManagerException($e->getMessage(), $e->getCode());
        }

        if (empty($result)) {
            return array();
        }

        return $this->convertToPods("edge", $result);
    }

    /**
     * Get the neighbour vertices connected to this vertex via some edge. The vertices and their connecting edges can be filtered by AQL.
     * @param Document|AModel|string A vertex pod, model or string of the vertex id.
     * @param string $direction "in" for inbound neighbours, "out" for outbound neighbours and "any" for all neighbours.
     * @param string $label     A string representing one label or an array of labels we want the inbound edges to have.
     * @param string $aql       An optional AQL fragment if we want to filter the edges or vertices, for example:
     *                    FILTER doc.edge.someproperty == "somevalue" && doc.vertex.anotherproperty == "anothervalue"
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function getNeighbours($model, $direction = "any", $label = null, $aql = "", $params = array(), $placeholder = "doc")
    {
        $id = $this->getVertexId($model);

        if (!$id) {
            return array();
        }

        $vertexCollection = $this->_toolbox->generateBindingParameter('@vertexCollection', $params);
        $edgeCollection = $this->_toolbox->generateBindingParameter('@edgeCollection', $params);
        $vertexParameter = $this->_toolbox->generateBindingParameter('vertexid', $params);
        $directionParameter = $this->_toolbox->generateBindingParameter('direction', $params);

        if (!$label) {
            $query = "FOR $placeholder in NEIGHBORS(@$vertexCollection, @$edgeCollection, @$vertexParameter, @$directionParameter) " . $aql . " return $placeholder.vertex";
        } else {
            $labelParameter = $this->_toolbox->generateBindingParameter('label', $params);
            $params[$labelParameter] = $label;
            $query = "FOR $placeholder in NEIGHBORS(@$vertexCollection, @$edgeCollection, @$vertexParameter, @$directionParameter, [{'\$label': @$labelParameter}]) " . $aql . " return $placeholder.vertex";
        }

        $params[$vertexCollection] = $this->_toolbox->getVertexCollectionName();
        $params[$edgeCollection] = $this->_toolbox->getEdgeCollectionName();
        $params[$vertexParameter] = $id;
        $params[$directionParameter] = $direction;

        try {
            $result = $this->_toolbox->getQuery()->getAll($query, $params);
        } catch (\Exception $e) {
            throw new GraphManagerException($e->getMessage(), $e->getCode());
        }

        if (empty($result)) {
            return array();
        }

        return $this->convertToPods("vertex", $result);
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
     * Converts the an array of associative arrays (each representing a document) received from the server into pods.
     * @param  string $type   The collection type. For graphs, only "vertex" or "edge" is valid.
     * @param  array  $result The array of documents to convert.
     * @return array
     */
    public function convertToPods($type, $result)
    {
        $converted = $this->_toolbox->getPodManager()->convertToPods($type, $result);

        foreach ($converted as $model) {

            $model->getPod()->setSaved();
        }

        return $converted;
    }
}
