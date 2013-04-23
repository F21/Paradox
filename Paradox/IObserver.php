<?php
namespace Paradox;
/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Observer interface
 * An interface that must be implemented by observers or listeners that wish to listen to events.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
interface IObserver
{
    /**
     * Called when an observable signals an event.
     * @param Event $eventObject
     */
    public function onEvent(Event $eventObject);
}
