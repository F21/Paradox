<?php
namespace Paradox;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Event
 * An event object passed to listeners that contain details about the event.
 *
 * @version 1.3.0
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Event
{
    /**
     * The name or type of the event.
     * @var string
     */
    private $_eventName;

    /**
     * Any extra data or details that can be useful for the listeners.
     * @var mixed
     */
    private $_object;

    /**
     * Whether this event has been stopped.
     * @var boolean
     */
    private $_propagationStopped = false;

    /**
     * Create the event object and set the event type and data.
     * @param string $event  The type of event.
     * @param mixed  $object Data or details useful for listeners.
     */
    public function __construct($event, $object)
    {
        $this->_eventName = $event;
        $this->_object = $object;
    }

    /**
     * Get the type of event.
     * @return string
     */
    public function getEvent()
    {
        return $this->_eventName;
    }

    /**
     * Get the additional data for the listeners.
     * @return mixed
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * Stop the event from propagating to other listeners.
     */
    public function stopPropagation()
    {
        $this->_propagationStopped = true;
    }

    /**
     * Checks whether the event has been stopped.
     * @return boolean
     */
    public function propagationStopped()
    {
        return $this->_propagationStopped;
    }
}
