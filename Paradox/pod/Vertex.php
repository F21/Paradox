<?php
namespace Paradox\pod;
use Paradox\Toolbox;
use Paradox\AModel;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Vertex pod
 * Represents an ArangoDB vertex. Implements IObserver to listen to events from the Pod Manager.
 *
 * @version 1.3.0
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Vertex extends Document
{
    /**
     * Instantiates the pod.
     * @param Toolbox $toolbox A reference to the toolbox that owns this pod.
     * @param array   $data    Any data that we wish to instantiate this pod with.
     * @param string  $new     Whether this pod should be marked as new (never been saved to the server).
     */
    public function __construct(Toolbox $toolbox, array $data = array(), $new = true)
    {
        parent::__construct($toolbox, "vertex", $data, $new);
    }

    /**
     * Create an edge connecting this vertex to another vertex.
     * @param  AModel $to    The model we wish to connect to.
     * @param  string $label An optional label for this edge.
     * @return Edge
     */
    public function relateTo(AModel $to, $label = null)
    {
        $edge = $this->_toolbox->getPodManager()->dispense("edge", $label);
        $edge->setTo($to);
        $edge->setFrom($this->getModel());

        return $edge;
    }

    /**
     * Get all inbound edges to this vertex. The edges can be filtered by their labels and AQL.
     * @param  string $label       A string representing one label or an array of labels we want the inbound edges to have.
     * @param  string $aql         An optional AQL fragment if we want to filter the edges, for example: FILTER doc.someproperty == "somevalue"
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function getInboundEdges($label = null, $aql = "", $params = array(), $placeholder = "doc")
    {
        return $this->_toolbox->getGraphManager()->getInboundEdges($this->getId(), $label, $aql, $params, $placeholder);
    }

    /**
     * Get all outbound edges to this vertex. The edges can be filtered by their labels and AQL.
     * @param  string $label       A string representing one label or an array of labels we want the inbound edges to have.
     * @param  string $aql         An optional AQL fragment if we want to filter the edges, for example: FILTER doc.someproperty == "somevalue"
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function getOutboundEdges($label = null, $aql = "", $params = array(), $placeholder = "doc")
    {
        return $this->_toolbox->getGraphManager()->getOutboundEdges($this->getId(), $label, $aql, $params, $placeholder);
    }

    /**
     * Get all edges connected to this vertex. The edges can be filtered by their labels and AQL.
     * @param  string $label       A string representing one label or an array of labels we want the inbound edges to have.
     * @param  string $aql         An optional AQL fragment if we want to filter the edges, for example: FILTER doc.someproperty == "somevalue"
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function getEdges($label = null, $aql = "", $params = array(), $placeholder = "doc")
    {
        return $this->_toolbox->getGraphManager()->getEdges($this->getId(), $label, $aql, $params, $placeholder);
    }

    /**
     * Get the neighbour vertices connected to this vertex via some edge. The vertices and their connecting edges can be filtered by AQL.
     * @param string $direction "in" for inbound neighbours, "out" for outbound neighbours and "any" for all neighbours.
     * @param string $label     A string representing one label or an array of labels we want the inbound edges to have.
     * @param string $aql       An optional AQL fragment if we want to filter the edges or vertices, for example:
     *                    FILTER doc.edge.someproperty == "somevalue" && doc.vertex.anotherproperty == "anothervalue"
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function getNeighbours($direction = "any", $label = null, $aql = "", $params = array(), $placeholder = "doc")
    {
       return $this->_toolbox->getGraphManager()->getNeighbours($this->getId(), $direction, $label, $aql, $params, $placeholder);
    }

    /**
     * Returns an ArangoDB-PHP vertex object representing this vertex.
     * @return \triagens\ArangoDb\Vertex
     */
    public function toDriverDocument()
    {
        $vertex = new \triagens\ArangoDb\Vertex;

        foreach ($this->_data as $key => $value) {
            $vertex->set($key, $value);
        }

        if ($this->_id) {
            $vertex->setInternalId($this->_id);
        }

        if ($this->_key) {
            $vertex->setInternalKey($this->_key);
        }

        if ($this->_rev) {
            $vertex->setRevision($this->_rev);
        }

        return $vertex;
    }

}
