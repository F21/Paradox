<?php
namespace Paradox\toolbox;
use Paradox\exceptions\PodManagerException;
use Paradox\exceptions\PodException;
use Paradox\Toolbox;
use Paradox\pod\Document;
use Paradox\pod\Vertex;
use Paradox\pod\Edge;
use Paradox\AModel;
use Paradox\AObservable;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Pod manager
 * Manages the lifecycle (dispense, load data, save, delete) of pods.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class PodManager extends AObservable
{
    /**
     * A reference to the toolbox.
     * @var Toolbox
     */
    private $_toolbox;

    /**
     * Instantiates the pod manager.
     * @param Toolbox $toolbox
     */
    public function __construct(Toolbox $toolbox)
    {
        $this->_toolbox = $toolbox;
    }

    /**
     * Sets up a pod enclosed in a model.
     * @param  string $collection The type of pod you want to dispense. For graphs, only "vertex" and "edge" is allowed. For documents, the name of the collection.
     * @param  string $label      If you are dispensing an edge, you can optionally provide a label for that edge.
     * @return AModel
     */
    public function dispense($type, $label = null)
    {
        if ($label !== null && (!$this->_toolbox->isGraph() || ($this->_toolbox->isGraph() && strtolower($type) != "edge"))) {
            throw new PodManagerException("Only Edge pods can have a label passed into dispense().");
        }

        //Graph pods
        if ($this->_toolbox->isGraph()) {

            switch (strtolower($type)) {

                case "vertex":
                    $model = $this->createVertex();
                    break;

                case "edge":
                    $model = $this->createEdge();

                    if ($label) {
                        $model->set('$label', $label);
                    }

                    break;

                default:
                    throw new PodManagerException("When dispensing pods for graphs, only the types 'vertex' and 'edge' are allowed. You provided '$type'.");
            }

        //Document pods
        } else {
            $model = $this->createDocument($type);
        }

        //Signal here
        $this->notify("after_dispense", $model->getPod());

        return $model;
    }

    /**
     * Persists a model to the server.
     * @param  AModel $model
     * @return int    The id of the saved pod.
     */
    public function store(AModel $model)
    {
        $driver = $this->_toolbox->getDriver();
        $pod = $model->getPod();

        //Signal here
        $this->notify("before_store", $pod);

        try {
            switch ($pod) {

                case $pod instanceof Vertex:
                    if ($pod->isNew()) {
                        $doc = $pod->toDriverDocument();
                        $driver->saveVertex($this->_toolbox->getGraph(), $doc);
                        $pod->setId($doc->getInternalId());
                    } else {
                        $doc = $pod->toDriverDocument();
                        $driver->replaceVertex($this->_toolbox->getGraph(), $pod->getId(), $doc);
                    }
                    break;

                case $pod instanceof Edge:

                    //Check to see if we have existing keys for the _to and _from vertices
                    $fromKey = $pod->getFromKey();
                    $toKey = $pod->getToKey();

                    //If we don't have a key for from
                    if (!$fromKey) {

                        //Get the from vertex
                        $from = $pod->getFrom();

                        //Save the vertices to get a key for them
                        if ($from) {
                            if ($from->hasChanged()) {
                                $this->store($from);
                                $fromKey = $from->getPod()->getId();
                            }

                            //If there are no vertices, throw an error
                        } else {
                            throw new PodManagerException("An edge must have a valid 'from' vertex.");
                        }
                    }

                    //If we don't have a key for to
                    if (!$toKey) {

                        //Get the from vertex
                        $to = $pod->getTo();

                        //Save the vertices to get a key for them
                        if ($to) {
                            if ($to->hasChanged()) {
                                $this->store($to);
                                $toKey = $to->getPod()->getId();
                            }

                            //If there are no vertices, throw an error
                        } else {
                            throw new PodManagerException("An edge must have a valid 'to' vertex.");
                        }
                    }

                    if ($pod->isNew()) {
                        $doc = $pod->toDriverDocument();

                        $driver->saveEdge($this->_toolbox->getGraph(), $fromKey, $toKey, null, $doc);
                        $pod->setId($doc->getInternalId());
                    } else {
                        //We delete the pod then save it, because ArangoDB does not provide a way to update the To and From vertices.
                        $this->delete($model);
                        $doc = $pod->toDriverDocument();
                        $driver->saveEdge($this->_toolbox->getGraph(), $fromKey, $toKey, null, $doc);
                    }
                    break;

                default:
                    $doc = $pod->toDriverDocument();

                    if ($pod->isNew()) {
                        $driver->save($pod->getType(), $doc);
                        $pod->setId($doc->getInternalId());
                    } else {
                        $driver->replace($doc);
                    }

            }
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new PodManagerException($normalised['message'], $normalised['code']);
        }

        $pod->setSaved();
        $pod->setRevision($doc->getRevision());

        //Signal here
        $this->notify("after_store", $pod);

        return $pod->getKey();
    }

    /**
     * Deletes a model from the server.
     * @param  AModel  $model
     * @return boolean
     */
    public function delete(AModel $model)
    {
        $driver = $this->_toolbox->getDriver();
        $pod = $model->getPod();

        //Signal here
        $this->notify("before_delete", $pod);

        try {
            if ($pod instanceof Vertex) {
                $driver->removeVertex($this->_toolbox->getGraph(), $pod->getId());
            } elseif ($pod instanceof Edge) {
                $driver->removeEdge($this->_toolbox->getGraph(), $pod->getId());
            } else {
                $driver->delete($pod->toDriverDocument());
            }
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new PodManagerException($normalised['message'], $normalised['code']);
        }

        //Signal here
        $this->notify("after_delete", $pod);

        return true;
    }

    /**
     * Loads a model from the server using the collection type and the id. Returns null if the pod could not be found or loaded.
     * @param  string $collection The collection to load from. For graphs, this can only be "vertex" or "edge".
     * @param  string $id         The id (key in ArangoDB jargon) of the document, edge or vertex.
     * @return AModel
     */
    public function load($type, $id)
    {
        $driver = $this->_toolbox->getDriver();

        try {
            if ($this->_toolbox->isGraph()) {

                switch (strtolower($type)) {

                    case "vertex":
                        $vertex = $driver->getVertex($this->_toolbox->getGraph(), $id);

                        return $this->convertDriverDocumentToPod($vertex);
                    case "edge":
                        $edge = $driver->getEdge($this->_toolbox->getGraph(), $id);

                        return $this->convertDriverDocumentToPod($edge);
                    default:
                        throw new PodManagerException("For graphs, only the types 'vertex' and 'edge' can be loaded.");
                }

            } else {
                $document = $driver->getById($type, $id);

                return $this->convertDriverDocumentToPod($document);
            }
        } catch (\Exception $e) {
        	
        	//Rethrow the exception from the try block
        	if($e instanceof PodManagerException){
        		throw $e;
        	
        	//Otherwise just return a null if the pod does not exist, or if there is an error.
        	}else{
        		return null;
        	}
        }

    }

    /**
     * Converts an array of documents into pods. The documents in each array can be an ArangoDB-PHP document, edge or vertex or an associative array.
     * @param  string $type      The type the resulting pods should have.
     * @param  array  $documents An array of documents or associative arrays.
     * @return array
     */
    public function convertToPods($type, array $documents)
    {
        $result = array();

        foreach ($documents as $document) {

            if ($document instanceof \triagens\ArangoDb\Document) {
                $model = $this->convertDriverDocumentToPod($document);

                $result[$document->getInternalId()] = $model;

            } else {
                $model = $this->convertArrayToPod($type, $document);

                if (isset($document['_id'])) {
                    $result[$document['_id']] = $model;
                } else {
                    $result[] = $model;
                }
            }
        }

        return $result;
    }

    /**
     * Converts an associative array to a pod.
     * @param  string $type The type of the resulting pod.
     * @param  array  $data An associative array containing the data for the pod.
     * @return AModel
     */
    public function convertArrayToPod($type, array $data)
    {
        $model = $this->dispense($type);
        $pod = $model->getPod();

        foreach ($data as $property => $value) {

            switch ($property) {

                case "_id":
                    $pod->setId($value);
                    break;

                case "_key":
                    break;

                case "_rev":
                    $pod->setRevision($value);
                    break;

                default:
                    $pod->set($property, $value);
                    break;
            }
        }

        //Signal here
        $this->notify("after_open", $model->getPod());

        return $model;
    }

    /**
     * Converts an ArangoDB-PHP document, edge or vertex into a pod.
     * @param  \triagens\ArangoDb\Document $driverDocument The ArangoDB-PHP document, edge or vertex.
     * @return AModel
     */
    public function convertDriverDocumentToPod(\triagens\ArangoDb\Document $driverDocument)
    {
        switch ($driverDocument) {
            case $driverDocument instanceof \triagens\ArangoDb\Vertex:
                $model = $this->dispense("vertex");
                break;

            case $driverDocument instanceof \triagens\ArangoDb\Edge:
                $model = $this->dispense("edge");
                break;

            default:
                @list($collection,) = explode('/', $driverDocument->getInternalId(), 2);
                $model = $this->dispense($collection);
        }

        $model->getPod()->loadFromDriver($driverDocument);
        $model->getPod()->setSaved();

        //Signal here
        $this->notify("after_open", $model->getPod());

        return $model;
    }

    /**
     * Convinence function to instantiate a vertex, hook up its events and set up its model.
     * @param array  $data An optional array of data that will be loaded into the vertex.
     * @param string $new  Whether this pod should be marked as new (never saved to the server).
     */
    private function createVertex(array $data = array(), $new = true)
    {
        $vertex = new Vertex($this->_toolbox, $data, $new);
        $this->attachEventsToPod($vertex);

        return $this->setupModel("vertex", $vertex);
    }

    /**
     * Convinence function to instantiate an edge, hook up its events and set up its model.
     * @param array  $data An optional array of data that will be loaded into the edge.
     * @param string $new  Whether this pod should be marked as new (never saved to the server).
     */
    private function createEdge(array $data = array(), $new = true)
    {
        $edge = new Edge($this->_toolbox, $data, $new);
        $this->attachEventsToPod($edge);

        return $this->setupModel("edge", $edge);
    }

    /**
     * Convinence function to instantiate a document, hook up its events and set up its model.
     * @param array  $data An optional array of data that will be loaded into the document.
     * @param string $new  Whether this pod should be marked as new (never saved to the server).
     */
    private function createDocument($type, $data = array(), $new = true)
    {
        $document = new Document($this->_toolbox, $type, $data, $new);
        $this->attachEventsToPod($document);

        return $this->setupModel($type, $document);
    }

    /**
     * Convience function that hooks up events emmited by this pod manager to the pod.
     * @param Document $pod The pod we want to hook up events to.
     */
    private function attachEventsToPod(Document $pod)
    {
        $this->attach(array('after_dispense', 'after_open', 'before_store', 'after_store', 'before_delete', 'after_delete'), $pod);
    }

    /**
     * Convinence function that sets up the model for a pod and associates the model with it.
     * @param  string              $type The type of the pod.
     * @param  Document            $pod  The pod we want to set up the model for.
     * @throws PodManagerException
     * @return Ambigous            AModel
     */
    private function setupModel($type, Document $pod)
    {
        $name = $this->_toolbox->formatModel($type);

        $model = new $name();

        if (!($model instanceof AModel)) {
            throw new PodManagerException("Custom models must inherit from the Paradox Model class.");
        }

        $model->loadPod($pod);

        $pod->loadModel($model);

        return $model;
    }

    /**
     * Validates the type to make sure it is valid in the current context. For graphs, a type other than "edge" or "vertex" would be invalid.
     * @param  string  $type The type to validate.
     * @return boolean
     */
    public function validateType($type)
    {
        if ($this->_toolbox->isGraph()) {

            if (!in_array(strtolower($type), array('edge', 'vertex'))) {
                return false;
            }
        }

        return true;
    }
}
