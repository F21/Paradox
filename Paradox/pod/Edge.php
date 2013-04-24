<?php
namespace Paradox\pod;
use Paradox\Toolbox;
use Paradox\AModel;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Edge pod
 * Represents an ArangoDB edge. Implements IObserver to listen to events from the Pod Manager.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Edge extends Document
{
    /**
     * Store a reference to the "from" vertex.
     * @var AModel
     */
    protected $_from;

    /**
     * Store a reference to the "to" vertex.
     * @var AModel
     */
    protected $_to;

    /**
     * Instantiates the pod.
     * @param Toolbox $toolbox      A reference to the toolbox that owns this pod.
     * @param array   $data         Any data that we wish to instantiate this pod with.
     * @param string  $new          Whether this pod should be marked as new (never been saved to the server).
     * @param string  $internalFrom The id (document handle) of the "from" vertex.
     * @param string  $internalTo   The id (document handle) of the "to" vertex.
     */
    public function __construct(Toolbox $toolbox, array $data = array(), $new = true, $internalFrom = null, $internalTo = null)
    {
        parent::__construct($toolbox, "edge", $data, $new);

        $this->setInternalFrom($internalFrom);
        $this->setInternalTo($internalTo);
    }

    /**
     * A convinence function to set the label of the edge.
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->set('$label', $label);
    }

    /**
     * A convinence function to get the label of the edge.
     * @return null|string
     */
    public function getLabel()
    {
        return $this->get('$label');
    }

    /**
     * Set the "to" vertex of the edge.
     * @param AModel $to
     */
    public function setTo(AModel $to)
    {
        $this->_to = $to;
        $this->setInternalTo($to->getPod()->getId());
    }

    /**
     * Get the key (id without the collection) of the to node by checking for it in the actual _to property or checking an internal property.
     * This is used because when documents are retrieved from the server, we will not have a model in the _to property.
     * This allows us to lazy load any vertices we need later.
     * @return null|string
     */
    public function getToKey()
    {
        if (isset($this->_to)) {
            return $this->_to->getPod()->getKey();
        } elseif (isset($this->_data['_to'])) {
            return $this->parseIdForKey($this->_data['_to']);
        } else {
            return null;
        }
    }

    /**
     * Set the "from" vertex of the edge.
     * @param AModel $from
     */
    public function setFrom(AModel $from)
    {
        $this->_from = $from;
        $this->setInternalFrom($from->getPod()->getId());
    }

    /**
     * Get the key (id without the collection) of the to node by checking for it in the actual _from property or checking an internal property.
     * This is used because when documents are retrieved from the server, we will not have a model in the _from property.
     * This allows us to lazy load any vertices we need later.
     * @return null|string
     */
    public function getFromKey()
    {
        if (isset($this->_from)) {
            return $this->_from->getPod()->getKey();
        } elseif (isset($this->_data['_from'])) {
            return $this->parseIdForKey($this->_data['_from']);
        } else {
            return null;
        }
    }

    /**
     * Get the from vertex. If we do not have the vertex yet (edge was loaded from the server), then fetch it from the server.
     * @return null|AModel
     */
    public function getFrom()
    {
        if (isset($this->_from)) {
            return $this->_from;
        }

        if (!isset($this->_from) && isset($this->_data['_from'])) {
            $toolbox = $this->_toolbox;

            $driverVertex = $toolbox->getDriver()->getVertex($toolbox->getGraph(), $this->parseIdForKey($this->_data['_from']));

            $this->_from = $toolbox->getPodManager()->convertDriverDocumentToPod($driverVertex);

            return $this->_from;
        }

        return null;
    }

    /**
     * Get the to vertex. If we do not have the vertex yet (edge was loaded from the server), then fetch it from the server.
     * @return null|AModel
     */
    public function getTo()
    {
        if (isset($this->_to)) {
            return $this->_to;
        }

        if (!isset($this->_to) && isset($this->_data['_to'])) {
            $toolbox = $this->_toolbox;

            $driverVertex = $toolbox->getDriver()->getVertex($toolbox->getGraph(), $this->parseIdForKey($this->_data['_to']));

            $this->_to = $toolbox->getPodManager()->convertDriverDocumentToPod($driverVertex);

            return $this->_to;
        }

        return null;
    }

    /**
     * Set the internal id pointing to the "to" vertex.
     * @param string $to
     */
    protected function setInternalTo($to)
    {
        $this->_data['_to'] = $to;
    }

    /**
     * Set the internal id pointing to the "from" vertex.
     * @param string $from
     */
    protected function setInternalFrom($from)
    {
        $this->_data['_from'] = $from;
    }

    /**
     * Returns an ArangoDB-PHP edge object representing this edge.
     * @return \triagens\ArangoDb\Edge
     */
    public function toDriverDocument()
    {
        $edge = new \triagens\ArangoDb\Edge;

        foreach ($this->_data as $key => $value) {
            $edge->set($key, $value);
        }

        if ($this->_id) {
            $edge->setInternalId($this->_id);
        }

        if ($this->_key) {
            $edge->setInternalKey($this->_key);
        }

        if ($this->_rev) {
            $edge->setRevision($this->_rev);
        }

        return $edge;
    }
}
