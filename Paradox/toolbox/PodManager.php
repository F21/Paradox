<?php
namespace Paradox\toolbox;
use Paradox\exceptions\PodManagerException;
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
 * @version 1.3.0
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

        $id = null;

        try {
            switch ($pod) {

                   case $pod instanceof Vertex:
                       if ($pod->isNew()) {

                           if ($this->hasTransaction()) {
                               $id = $this->determinePreviouslyStored($model);
                               $this->_toolbox->getTransactionManager()->addWriteCollection($this->_toolbox->getVertexCollectionName());

                               //In a transaction, since all commands are executed in a block on commit, the pod will not have a saved state if it has a previous
                               //store command. This determines if that is the case and rewrites the command as a replace.
                               if ($id !== false) {
                                       return $this->addTransactionCommand("db.{$this->_toolbox->getVertexCollectionName()}.replace(result.$id._id, {$pod->toTransactionJSON()});", "PodManager:store", $model, true);
                               } else {
                                       return $this->addTransactionCommand("graph.addVertex(null, {$pod->toTransactionJSON()})._properties;", "PodManager:store", $model, true);
                               }

                           } else {
                               $doc = $pod->toDriverDocument();
                               $driver->saveVertex($this->_toolbox->getGraph(), $doc);
                               $id = $doc->getInternalId();
                           }

                       } else {

                           if ($this->hasTransaction()) {
                               $this->_toolbox->getTransactionManager()->addWriteCollection($this->_toolbox->getVertexCollectionName());

                               return $this->addTransactionCommand("db.{$this->_toolbox->getVertexCollectionName()}.replace('{$pod->getId()}', {$pod->toTransactionJSON()});", "PodManager:store", $model, true);
                           } else {
                               $doc = $pod->toDriverDocument();
                               $driver->replaceVertex($this->_toolbox->getGraph(), $pod->getId(), $doc);
                           }
                    }
                    break;

                   case $pod instanceof Edge:

                       //Check to see if we have existing keys for the _to and _from vertices
                       $fromKey = $pod->getFromKey();
                       $toKey = $pod->getToKey();
                       $fromKeyIsJSVar = false;
                       $toKeyIsJSVar = false;

                       //If we don't have a key for from
                       if (!$fromKey) {

                           //Get the from vertex
                        $from = $pod->getFrom();

                        //Save the vertices to get a key for them
                           if ($from) {
                               if ($from->hasChanged()) {

                                   if ($this->hasTransaction()) {
                                       $fromKey = $this->store($from);
                                       $fromKeyIsJSVar = true;
                                   } else {
                                       $this->store($from);
                                       $fromKey = $from->getPod()->getId();
                                   }
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

                                   if ($this->hasTransaction()) {
                                       $toKey = $this->store($to);
                                       $toKeyIsJSVar = true;
                                   } else {
                                       $this->store($to);
                                       $toKey = $to->getPod()->getId();
                                   }
                               }

                        //If there are no vertices, throw an error
                        } else {
                            throw new PodManagerException("An edge must have a valid 'to' vertex.");
                        }
                       }

                       if ($pod->isNew()) {

                           if ($this->hasTransaction()) {

                               $id = $this->determinePreviouslyStored($model);
                               $this->_toolbox->getTransactionManager()->addWriteCollection($this->_toolbox->getEdgeCollectionName());

                               //In a transaction, since all commands are executed in a block on commit, the pod will not have a saved state if it has a previous
                               //store command. This determines if that is the case and rewrites the command as a replace.
                               if ($id !== false) {
                                       $this->addTransactionCommand("function(){graph.removeEdge(graph.getEdge(result.$id._id)); return true;}();", "PodManager:store", $model, true);
                                       $this->addTransactionCommand($this->generateCreateEdgeCommand($fromKeyIsJSVar, $fromKey, $toKeyIsJSVar, $toKey, $pod->toTransactionJSON(), "result.$id._key", true), "PodManager:store", $model, true);
                               } else {
                                       $this->addTransactionCommand($this->generateCreateEdgeCommand($fromKeyIsJSVar, $fromKey, $toKeyIsJSVar, $toKey, $pod->toTransactionJSON()), "PodManager:store", $model, true);
                               }
                           } else {
                               $doc = $pod->toDriverDocument();

                               $driver->saveEdge($this->_toolbox->getGraph(), $fromKey, $toKey, null, $doc);
                               $id = $doc->getInternalId();
                           }

                       } else {
                        //We delete the pod then save it, because ArangoDB does not provide a way to update the To and From vertices.
                        if ($this->hasTransaction()) {
                            $this->_toolbox->getTransactionManager()->addWriteCollection($this->_toolbox->getEdgeCollectionName());
                            $this->addTransactionCommand("function(){graph.removeEdge(graph.getEdge('{$pod->getId()}')); return true;}();", "PodManager:store", $model, true);
                            $this->addTransactionCommand($this->generateCreateEdgeCommand($fromKeyIsJSVar, $fromKey, $toKeyIsJSVar, $toKey, $pod->toTransactionJSON(), $pod->getKey()), "PodManager:store", $model, true);
                        } else {
                            $id = $pod->getId();
                            $revision = $pod->getRevision() + mt_rand(0, 1000);
                            $this->delete($model);

                            //Add the id and revision back, because deleting removes it
                            $pod->setId($id);
                            $pod->setRevision($revision);

                            $doc = $pod->toDriverDocument();
                            $driver->saveEdge($this->_toolbox->getGraph(), $fromKey, $toKey, null, $doc);
                        }
                    }
                    break;

                default:
                    $doc = $pod->toDriverDocument();

                    if ($pod->isNew()) {

                        if ($this->hasTransaction()) {
                            $id = $this->determinePreviouslyStored($model);
                            $this->_toolbox->getTransactionManager()->addWriteCollection($pod->getType());

                            //In a transaction, since all commands are executed in a block on commit, the pod will not have a saved state if it has a previous
                            //store command. This determines if that is the case and rewrites the command as a replace.
                            if ($id !== false) {
                                $this->addTransactionCommand("db.{$pod->getType()}.replace(result.$id._id, {$pod->toTransactionJSON()}, true);", "PodManager:store", $model);
                            } else {
                                $this->addTransactionCommand("db.{$pod->getType()}.save({$pod->toTransactionJSON()});", "PodManager:store", $model);
                            }

                        } else {
                            $driver->save($pod->getType(), $doc);
                            $id = $doc->getInternalId();
                        }

                    } else {

                        if ($this->hasTransaction()) {
                            $this->_toolbox->getTransactionManager()->addWriteCollection($pod->getType());
                            $this->addTransactionCommand("db.{$pod->getType()}.replace('{$pod->getId()}', {$pod->toTransactionJSON()}, true);", "PodManager:store", $model);
                        } else {
                            $driver->replace($doc);
                        }
                    }

            }
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new PodManagerException($normalised['message'], $normalised['code']);
        }

        if (!$this->hasTransaction()) {
            return $this->processStoreResult($pod, $doc->getRevision(), $id);
        }
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

                if ($this->hasTransaction()) {
                    $id = $this->determinePreviouslyStored($model);
                    $this->_toolbox->getTransactionManager()->addWriteCollection($this->_toolbox->getVertexCollectionName());

                    if ($id !== false) {
                        $this->addTransactionCommand("function(){graph.removeVertex(graph.getVertex(result.$id._id)); return true;}();", "PodManager:delete", $model, true);
                    } else {
                        $this->addTransactionCommand("function(){graph.removeVertex(graph.getVertex('{$pod->getId()}')); return true;}();", "PodManager:delete", $model, true);
                    }

                } else {
                    $driver->removeVertex($this->_toolbox->getGraph(), $pod->getId());
                }

            } elseif ($pod instanceof Edge) {

                if ($this->hasTransaction()) {
                    $id = $this->determinePreviouslyStored($model);
                    $this->_toolbox->getTransactionManager()->addWriteCollection($this->_toolbox->getEdgeCollectionName());

                    if ($id !== false) {
                        $this->addTransactionCommand("function(){graph.removeEdge(graph.getEdge(result.$id._id)); return true;}();", "PodManager:delete", $model, true);
                    } else {
                        $this->addTransactionCommand("function(){graph.removeEdge(graph.getEdge('{$pod->getId()}')); return true;}();", "PodManager:delete", $model, true);
                    }

                } else {
                    $driver->removeEdge($this->_toolbox->getGraph(), $pod->getId());
                }

            } else {

                if ($this->hasTransaction()) {

                    $id = $this->determinePreviouslyStored($model);
                    $this->_toolbox->getTransactionManager()->addWriteCollection($pod->getType());

                    if ($id !== false) {
                        $this->addTransactionCommand("function(){db.{$pod->getType()}.remove(result.$id._id, true); return true;}();", "PodManager:delete", $model);
                    } else {
                        $this->addTransactionCommand("function(){db.{$pod->getType()}.remove('{$pod->getId()}', true); return true;}();", "PodManager:delete", $model);
                    }

                } else {
                    $driver->delete($pod->toDriverDocument());
                }

            }
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new PodManagerException($normalised['message'], $normalised['code']);
        }

        if (!$this->hasTransaction()) {
            return $this->processDeleteResult($pod);
        }

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

                        if ($this->hasTransaction()) {
                            $this->_toolbox->getTransactionManager()->addReadCollection($this->_toolbox->getVertexCollectionName());
                            $this->addTransactionCommand("function(){var temp = graph.getVertex('$id'); return temp ? temp._properties : null}();", "PodManager:load", null, true, array('type' => $type));
                        } else {
                            $vertex = $driver->getVertex($this->_toolbox->getGraph(), $id);

                            return $this->convertDriverDocumentToPod($vertex);
                        }
                        break;

                    case "edge":

                        if ($this->hasTransaction()) {
                            $this->_toolbox->getTransactionManager()->addReadCollection($this->_toolbox->getEdgeCollectionName());
                            $this->addTransactionCommand("graph.getEdge('$id')._properties;", "PodManager:load", null, true, array('type' => $type));
                        } else {
                            $edge = $driver->getEdge($this->_toolbox->getGraph(), $id);

                            return $this->convertDriverDocumentToPod($edge);
                        }
                        break;

                    default:
                        throw new PodManagerException("For graphs, only the types 'vertex' and 'edge' can be loaded.");
                }

            } else {

                if ($this->hasTransaction()) {
                    $this->_toolbox->getTransactionManager()->addReadCollection($type);
                    $this->addTransactionCommand("db.$type.document('$id');", "PodManager:load", null, false, array('type' => $type));
                } else {
                    $document = $driver->getById($type, $id);

                    return $this->convertDriverDocumentToPod($document);
                }

            }
        } catch (\Exception $e) {

            //Rethrow the exception from the try block
            if ($e instanceof PodManagerException) {
                throw $e;

            //Otherwise just return a null if the pod does not exist, or if there is an error.
            } else {
                return null;
            }
        }

    }

    public function processDeleteResult($pod)
    {
        //Signal here
        $pod->resetMeta();
        $this->notify("after_delete", $pod);

        return true;
    }

    /**
     * Process the result after storing a pod.
     * @param Paraodx\pod\Document|Paradox\pod\Vertex|Paradox\pod\Edge $pod      The pod to process.
     * @param string                                                   $revision The revision of the pod.
     * @param string                                                   $id       The id of the pod.
     */
    public function processStoreResult($pod, $revision, $id = null)
    {
        $pod->setSaved();
        $pod->setRevision($revision);

        if ($id && $pod->getId() === null) {
            $pod->setId($id);
        }

        //Signal here
        $this->notify("after_store", $pod);

        return $pod->getKey();
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

        $pod->loadFromArray($data);

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
     * Generates the javascript command to create an edge (for use with transactions).
     * @param  boolean $fromKeyIsJSVar Whether the from key is a javascript variable name or not.
     * @param  string  $fromKey        The id of the from vertex.
     * @param  boolean $toKeyIsJSVar   Whether the to key is a javascript variable name or not.
     * @param  string  $toKey          The id of the to vertex.
     * @param  string  $data           JSON representation of the edge data.
     * @param  string  $id             An optional id for the edge.
     * @param  boolean $idIsVariable   Whether the id string is a javascript variable.
     * @return string
     */
    private function generateCreateEdgeCommand($fromKeyIsJSVar, $fromKey, $toKeyIsJSVar, $toKey, $data, $id = null, $idIsVariable = false)
    {
    	$command = "graph.addEdge(";
    	
        if ($fromKeyIsJSVar) {
            $command .= "graph.getVertex(result.$fromKey._id)";
        } else {
            $command .= "graph.getVertex('$fromKey')";
        }

        $command .= ", ";

        if ($toKeyIsJSVar) {
            $command .= "graph.getVertex(result.$toKey._id)";
        } else {
            $command .= "graph.getVertex('$toKey')";
        }
        
        $command .= ", ";
        
        if ($id) {
        
        	if ($idIsVariable) {
        		$command .= "$id";
        	} else {
        		$command .= "'$id'";
        	}
        
        } else {
        	$command .= "null";
        }

        $command .= ", $data)._properties;";

        return $command;
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
     * Because transactions are executed in 1 go, this helps us deteremine if the pod has a previous command to store it.
     * We can then use its id to work with it in the transaction.
     * @param  AModel         $model The model to determine whether to delete or not.
     * @return boolean|string
     */
    private function determinePreviouslyStored($model)
    {
        $store = $this->_toolbox->getTransactionManager()->searchCommandsByActionAndObject('PodManager:store', $model);
        $delete = $this->_toolbox->getTransactionManager()->searchCommandsByActionAndObject('PodManager:delete', $model);

        $storePosition = $store ? $store['position'] : -1;
        $deletePosition = $delete ? $delete['position'] : -1;

        if ($deletePosition >= $storePosition) {
            return false;
        } else {
            return $store['id'];
        }
    }

    /**
     * Whether the connection current has an active transaction.
     * @return boolean
     */
    private function hasTransaction()
    {
        return $this->_toolbox->getTransactionManager()->hasTransaction();
    }

    /**
     * Convinence function to add commands to the transaction.
     * @param unknown $command
     * @param unknown $action
     * @param string  $object
     * @param string  $isGraph
     */
    private function addTransactionCommand($command, $action, $object = null, $isGraph = false, $data = array())
    {
        return $this->_toolbox->getTransactionManager()->addCommand($command, $action, $object, $isGraph, $data);
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
