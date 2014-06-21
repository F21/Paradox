<?php
namespace Paradox\pod;
use Paradox\exceptions\PodException;
use Paradox\Toolbox;
use Paradox\IObserver;
use Paradox\Event;
use Paradox\AModel;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Document pod
 * Represents an ArangoDB document. Implements IObserver to listen to events from the Pod Manager.
 *
 * @version 1.3.0
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Document implements IObserver
{
    /**
     * Whether this pod is new (never saved to the server) or not.
     * @var boolean
     */
    protected $_new = true;

    /**
     * The keys and values of all document properties.
     * @var array
     */
    protected $_data = array();

    /**
     * The key of the document, for example: 123456. This is stored as a string to mitigate limitations of int.
     * @var string
     */
    protected $_key;

    /**
     * The revision of the document, for example: 45678. This is stored as a string to mitigate limitations of int.
     * @var string
     */
    protected $_rev;

    /**
     * The id or document handle of the document, for example: mycollection/123456.
     * @var string
     */
    protected $_id;

    /**
     * The type of the pod. For documents, this is the name of the collection. For graphs, only "vertex" or "edge" is valid.
     * @var string
     */
    protected $_type;

    /**
     * Stores the distance between this document and some point. This is only populated for geo queries.
     * @var float
     */
    protected $_distance;

    /**
     * Stores the reference point for the distance parameter. This is only populated for geo queries.
     * @var array
     */
    protected $_referenceCoordinates;

    /**
     * If the reference point is a pod, the pod's id is stored here. This is only populated for geo queries.
     * @var Document
     */
    protected $_referencePodId;

    /**
     * Whether the data of this pod has been changed.
     * @var boolean
     */
    protected $_changed = false;

    /**
     * A reference to the toolbox that manages this pod.
     * @var Toolbox
     */
    protected $_toolbox;

    /**
     * A reference to the model that wraps around this pod.
     * @var AModel
     */
    protected $_model;

    /**
     * Instantiates the pod.
     * @param Toolbox $toolbox A reference to the toolbox.
     * @param string  $type    The type of this pod.
     * @param array   $data    Any data that we wish to initalise this pod with.
     * @param string  $new     Whether this pod is new or not.
     */
    public function __construct(Toolbox $toolbox, $type, array $data = array(), $new = true)
    {
        $this->_data = $data;
        $this->_type = $type;
        $this->_new = $new;
        $this->_toolbox = $toolbox;

        if ($new) {
            $this->_changed = true;
        }
    }

    /**
     * Mark this pod as new (never saved to the server) if cloned.
     */
    public function __clone()
    {
        $this->_id  = null;
        $this->_key = null;
        $this->_rev = null;
        $this->_new = true;
        $this->_change = true;
    }

    /**
     * Set a property for this pod.
     * @param  string       $key   The property we wish to set.
     * @param  string       $value The value we wish to set.
     * @throws PodException
     */
    public function set($key, $value)
    {
        if (in_array($key, $this->getReservedFields())) {
            throw new PodException("You cannot set the property $key. This is reserved for system use.");
        }

        $this->_changed = true;

        $this->_data[$key] = $value;
    }

    /**
     * Remove a property for this pod.
     * @param  string       $key The property we wish to unset.
     * @throws PodException
     */
    public function remove($key)
    {
        if (in_array($key, $this->getReservedFields())) {
            throw new PodException("You cannot set the property $key. This is reserved for system use.");
        }

        $this->_changed = true;

        unset($this->_data[$key]);
    }

    /**
     * Get the value of a property from the pod.
     * @param  string       $key
     * @throws PodException
     * @return mixed
     */
    public function get($key)
    {
        if (in_array($key, $this->getReservedFields())) {

            throw new PodException("You cannot get the system property '$key'. Use the appropriate getter for it.");
        }

        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    /**
     * Prevent users from setting a key to the pod.
     * @param  string       $key
     * @throws PodException
     */
    public function setKey($key)
    {
        throw new PodException("You cannot set the _key for a pod. This value is maintained and generated automatically.");
    }

    /**
     * Set the revision for this pod.
     * @param string $revision
     */
    public function setRevision($revision)
    {
        $this->_rev = (string) $revision;
    }

    /**
     * Set the id for this pod. The id is only allowed to be set once, because once saved to the server, the id can't be changed.
     * @param  string       $id
     * @throws PodException
     */
    public function setId($id)
    {
        if ($this->_id !== null && $this->_id != $id) {
            throw new PodException('Cannot update the id of an existing document');
        }

        if (!preg_match('/^\w+\/\w+$/', $id)) {
            throw new PodException('Invalid format for document id');
        }

        @list(, $documentId) = explode('/', $id, 2);
        $this->_key = $documentId;
        $this->_id = (string) $id;
    }

    /**
     * Get the key of the document, for example: 123456.
     * @return null|string
     */
    public function getKey()
    {
        return isset($this->_key) ? $this->_key : null;
    }

    /**
     * Get the id of the document, for example: mycollection/123456
     * @return null|string
     */
    public function getId()
    {
        return isset($this->_id) ? $this->_id : null;
    }

    /**
     * Get the revision of the document, for example: 45678
     * @return null|string
     */
    public function getRevision()
    {
        return isset($this->_rev) ? $this->_rev : null;
    }

    /**
     * Sets the distance information to the pod. This is intended to be used by the API and is not for public comsumption.
     * @param  float        $distance  The distance from this pod to the reference pod in meters.
     * @param  float        $latitude  The latitude of the reference point.
     * @param  float        $longitude The longitude of the reference point.
     * @param  string       $podId     If the reference point is a pod, the pod id.
     * @throws PodException
     */
    public function setDistanceInfo($latitude, $longitude, $podId = null)
    {
        if (isset($this->_distance) || isset($this->_referenceCoordinates) || isset($this->_referencePodId)) {
            throw new PodException("Cannot update the distance info from an existing query.");
        }

        $this->_distance = $this->_data['_paradox_distance_parameter'];
        unset($this->_data['_paradox_distance_parameter']);

        $this->_referenceCoordinates = array('latitude' => $latitude, 'longitude' => $longitude);
        $this->_referencePodId = $podId;
    }

    /**
     * Return the distance from this pod to the reference in meters.
     * @return float
     */
    public function getDistance()
    {
        return isset($this->_distance) ? $this->_distance : null;
    }

    /**
     * Returns the coordinates of the reference.
     * @return array
     */
    public function getReferenceCoordinates()
    {
        return isset($this->_referenceCoordinates) ? $this->_referenceCoordinates : null;
    }

    public function getReferencePod()
    {
        if (!$this->_referencePodId) {
            return null;
        } else {
            $info = $this->_toolbox->parseId($this->_referencePodId);

            return $this->_toolbox->getPodManager()->load($info['collection'], $info['key']);
        }
    }

    /**
     * Get the coordinates (latitude and longitude) from this pod's first geo index if it exists.
     * @return null|array
     */
    public function getCoordinates()
    {
        $fields = $this->_toolbox->getCollectionManager()->getGeoFieldsForAQL($this->getType());

        //A geo1 index (field is an array with the coordinates).
        if ($fields) {
            if (count($fields) == 1) {

                $field = $this->get($fields[0]);

                return array('latitude' => $field[0], 'longitude' => $field[1]);

                //A geo2 index (2 fields each representing latitue and longitude).
            } else {
                $latitude = $this->get($fields[0]);
                $longitude = $this->get($fields[1]);

                return array('latitude' => $latitude, 'longitude' => $longitude);
            }
        }

        return null;
    }

    /**
     * Mark this pod as saved.
     */
    public function setSaved()
    {
        $this->_new = false;
        $this->_changed = false;
    }

    /**
     * Reset the meta data of the pod so that it appears as a new one.
     * The existing user added data is not reset.
     */
    public function resetMeta()
    {
        $this->_new = true;
        $this->_changed = true;
        $this->_id = null;
        $this->_key = null;
        $this->_rev = null;
    }

    /**
     * Return an associative array representation of this document.
     * @return array
     */
    public function toArray()
    {
        $result = array('_id' => $this->getId(), '_key' => $this->getKey(), '_rev' => $this->getRevision());

        return array_merge($result, $this->_data);
    }

    /**
     * Returns a JSON representation of this document.
     * @return string
     */
    public function toJSON()
    {
        $result = array('_id' => $this->getId(), '_key' => $this->getKey(), '_rev' => $this->getRevision());

        return json_encode(array_merge($result, $this->_data), JSON_FORCE_OBJECT);
    }

    /**
     * Returns a JSON representation of this document for transactions.
     * @return string
     */
    public function toTransactionJSON()
    {
        $result = array('_rev' => $this->getRevision());

        return json_encode(array_merge($result, $this->_data), JSON_FORCE_OBJECT);
    }

    /**
     * Returns an ArangoDB-PHP document object representing this pod.
     * @return \triagens\ArangoDb\Document
     */
    public function toDriverDocument()
    {
        $document = new \triagens\ArangoDb\Document;

        foreach ($this->_data as $key => $value) {
            $document->set($key, $value);
        }

        if ($this->_id) {
            $document->setInternalId($this->_id);
        }

        if ($this->_key) {
            $document->setInternalKey($this->_key);
        }

        if ($this->_rev) {
            $document->setRevision($this->_rev);
        }

        return $document;
    }

    /**
     * Get the type/collection of this pod.
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Whether this pod belongs to a graph.
     * @return boolean
     */
    public function isGraph()
    {
        return $this instanceof Vertex || $this instanceof Edge;
    }

    /**
     * Whether this pod is new (has never been saved to the server).
     */
    public function isNew()
    {
        return $this->_new;
    }

    /**
     * Whether this pod has been change (document properties updated).
     * @return boolean
     */
    public function hasChanged()
    {
        return $this->_changed;
    }

    /**
     * Load this pod with document data from a ArangoDB-PHP document object.
     * @param \triagens\ArangoDb\Document $driverDocument
     */
    public function loadFromDriver(\triagens\ArangoDb\Document $driverDocument)
    {
        $values = $driverDocument->getAll(array('_includeInternals' => true));

        foreach ($values as $key => $value) {

            if (!in_array($key, array('_id', '_key', '_rev'))) {
                $this->_data[$key] = $value;
            }
        }

        $this->setId($driverDocument->getInternalId());
        $this->setRevision($driverDocument->getRevision());
        $this->setSaved();
    }

    /**
     * Load this pod with document data from an array.
     * @param array $data
     */
    public function loadFromArray($data)
    {
        foreach ($data as $property => $value) {

            switch ($property) {

                case "_id":
                    $this->setId($value);
                    break;

                case "_key":
                    break;

                case "_rev":
                    $this->setRevision($value);
                    break;

                default:
                    $this->_data[$property] = $value;
                    break;
            }
        }
    }

    /**
     * Set the model for this pod. This can only be set once, as changing the model can cause unexpected results.
     * @param  AModel       $model
     * @throws PodException
     */
    public function loadModel(AModel $model)
    {
        if ($this->_model) {
            throw new PodException("You cannot change the model for this pod as things can break and lead to unexpected results.");
        }

        $this->_model = $model;
    }

    /**
     * Find the pods that are near this pod.
     * You can add an AQL fragment to process the list further, for example: FILTER doc.property != "unwanted value" SORT doc.name LIMIT 10
     * This pod will not be included in the returned list. An empty array is returned if no pods are found.
     * @param  string $aql         An optional AQL fragment that will be inserted after the FOR clause so you can FILTER, LIMIT, etc the query.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  number $limit       The maximum number of pods to find.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function near($aql = "", $params = array(), $limit = 100, $placeholder = "doc")
    {
        return $this->_toolbox->getFinder()->findAllNear($this->getType(), $this->getModel(), $aql, $params, $limit, $placeholder);
    }

    /**
     * Find the pods that are within a radius from this pod..
     * You can add an AQL fragment to process the list further, for example: FILTER doc.property != "unwanted value" SORT doc.name LIMIT 10
     * This pod will not be included in the returned list. An empty array is returned if no pods are found.
     * @param $radius The radius in meters.
     * @param  string $aql         An optional AQL fragment that will be inserted after the FOR clause so you can FILTER, LIMIT, etc the query.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function within($radius, $aql = "", $params = array(), $placeholder = "doc")
    {
        return $this->_toolbox->getFinder()->findAllWithin($this->getType(), $this->getModel(), $radius, $aql, $params, $placeholder);
    }

    /**
     * Get a reference to the model for this pod.
     * @return \Paradox\AModel
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * Get a list of reserved fields used by ArangoDB.
     * @return array
     */
    protected function getReservedFields()
    {
        return array('_id', '_key', '_rev');
    }

    /**
     * Given the id in ArangoDB format (mycollection/123456) parse it and return the key (123456).
     * @param  string $id
     * @return string
     */
    protected function parseIdForKey($id)
    {
        return $this->_toolbox->parseIdForKey($id);
    }

    /**
     * Performs a comparison against a toolbox to check if this pod belongs to that toolbox.
     * @param  Toolbox $toolbox The toolbox.
     * @return boolean
     */
    public function compareToolbox(Toolbox $toolbox)
    {
        return $this->_toolbox === $toolbox;
    }

    /**
     * When this pod recieves an event, process it accordingly.
     * In this case, the pod simply calls the various functions in model before and after each event.
     * @param Event $eventObject The event object.
     */
    public function onEvent(Event $eventObject)
    {
        if ($eventObject->getObject() === $this) {

            switch ($eventObject->getEvent()) {

                case 'after_dispense':
                    $this->getModel()->afterDispense();
                    break;

                case 'after_open':
                    $this->getModel()->afterOpen();
                    break;

                case 'before_store':
                    $this->getModel()->beforeStore();
                    break;

                case 'after_store':
                    $this->getModel()->afterStore();
                    break;

                case 'before_delete':
                    $this->getModel()->beforeDelete();
                    break;

                case 'after_delete':
                    $this->getModel()->afterDelete();
                    break;
            }

        }
    }

}
