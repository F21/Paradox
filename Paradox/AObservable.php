<?php
namespace Paradox;
use exceptions\ObservableException;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Observable
 * Abstract class that implements the observer patter which allows classes that extend it to fire events to its listeners.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
abstract class AObservable
{
    /**
     * A associative array containing events and the subscribers to those events.
     * @var array
     */
    protected $_observers = array();

    /**
     * Attach a listener.
     * @param  string|array        $events   The event type. This can be an array of event types or just a string for one event.
     * @param  IObserver           $observer The listener.
     * @throws ObservableException
     */
    public function attach($events, IObserver $observer)
    {
        if (is_array($events)) {
            foreach ($events as $event) {
                $this->doAttach($event, $observer);
            }
        } elseif (is_string($event)) {
            $this->doAttach($events, $observer);
        } else {
            throw new ObservableException("Event can only be a string containing the name of the event or an array of event names.");
        }
    }

    /**
     * Do the actual work of attaching the listener.
     * @param string    $event    The event.
     * @param IObserver $observer The listener.
     */
    protected function doAttach($event, IObserver $observer)
    {
        if (!array_key_exists($event, $this->_observers)) {
            $this->_observers[$event] = array();
        }

        if (in_array($observer, $this->_observers[$event], true)) {
            return;
        }

        array_push($this->_observers[$event], $observer);
    }

    /**
     * Detatch an observer from an event.
     * @param string    $event    The event to detach from.
     * @param IObserver $observer The observer that we want to detach.
     */
    public function detach($event, IObserver $observer)
    {
        if (!isset($this->_observers[$event])) {
            return;
        }

        foreach ($this->_observers[$event] as $key=> $attachedObserver) {
            if ($attachedObserver === $observer) {
                array_splice($this->_observers[$event], $key, 1);
            }
        }
    }

    /**
     * Remove all observers for an event.
     * @param string $event The event we wish to detach.
     */
    public function detachAllObserversForEvent($event)
    {
        if (!isset($this->_observers[$event])) {
            return;
        }

        unset($this->_observers[$event]);

    }

    /**
     * Remove all events for an observer.
     * @param IObserver $observerToRemove The observer we wish to detach.
     */
    public function detachAllEventsForObserver(IObserver $observerToRemove)
    {
        foreach ($this->_observers as $event => $observers) {
            foreach ($observers as $position => $observer) {

                if ($observer === $observerToRemove) {
                    array_splice($this->_observers[$event], $position, 1);
                    break; //Save cycles by breaking here since there is each observer is only attached to each event once.
                }
            }
        }
    }

    /**
     * Notify all observers with an event and the associate object/data that observers might find useful.
     * @param string $event  The event we wish to signal.
     * @param mixed  $object The object/data that we want to make avaliable to the observers.
     */
    public function notify($event, $object)
    {
        $eventObject = new Event($event, $object);

        if (isset($this->_observers[$event]) && is_array($this->_observers[$event])) {

            foreach ($this->_observers[$event] as $observer) {

                if ($eventObject->propagationStopped()) {
                    break;
                }

                $observer->onEvent($eventObject);
            }

        }
    }
}
