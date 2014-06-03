<?php
namespace Paradox;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Model formatter interface
 * All custom model formatters must implement this interface.
 *
 * @version 1.3.0
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
interface IModelFormatter
{
    /**
     * Returns the name of the class to instantiate given the type and whether this is for a graph or not.
     * @param Document $pod The document pod.
     * @param boolean $isGraph Whether this is for a connection that manages graphs or not.
     */
    public function formatModel($pod, $isGraph);
}
