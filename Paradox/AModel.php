<?php
namespace Paradox;
use Paradox\pod\Document;
use Paradox\exceptions\ModelException;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Model
 * This is an abstract class that must be extended by all custom models.
 * You can define your own custom methods and properties in your own models. In addition, the model provides
 * several events that you can act own during the lifecycle of a pod.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
abstract class AModel
{
    /**
     * Holds the pod representing a document, vertex or edge.
     * @var Paradox\pod\Document|Paradox\pod\Vertex|Paradox\pod\Edge
     */
    protected $_pod;

    /**
     * Loads a pod into this model.
     * @param Document $pod
     */
    public function loadPod(Document $pod)
    {
        if ($this->_pod) {
            throw new ModelException("You cannot change the pod for this model as things can break and lead to unexpected results.");
        }

        $this->_pod = $pod;
    }

    /**
     * Get the pod from this model.
     * @return \Paradox\Paradox\pod\Document|\Paradox\Paradox\pod\Vertex|\Paradox\Paradox\pod\Edge
     */
    public function getPod()
    {
        return $this->_pod;
    }

    /**
     * Called after the pod has been instantiated and setup.
     */
    public function afterDispense()
    {
    }

    /**
     * Called after the pod has been loaded with data. Note that this is only called for data that has been loaded from the server.
     */
    public function afterOpen()
    {
    }

    /**
     * Called before the pod is saved.
     */
    public function beforeStore()
    {
    }

    /**
     * Called after the pod is saved.
     */
    public function afterStore()
    {
    }

    /**
     * Called before the pod is deleted.
     */
    public function beforeDelete()
    {
    }

    /**
     * Called after the pod is deleted.
     */
    public function afterDelete()
    {
    }

    /**
     * Pass all calls to undefined functions in this model down to the pod.
     * @param  string $func The name of the method.
     * @param  array  $args Any arguments provided to the method.
     * @return mixed
     */
    public function __call($func, $args = array())
    {
        return call_user_func_array(array($this->_pod, $func), $args);
    }
}
