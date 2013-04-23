<?php
namespace Paradox;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Default model formatter
 * The default model formatter that just asks the library to instantiate \Paradox\GenericModel for everything.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class DefaultModelFormatter implements IModelFormatter
{
    /**
     * Returns \Paradox\GenericModel for all types.
     * @param string  $type    The type of the pod. For graphs, only "vertex" and "edge" is valid.
     * @param boolean $isGraph Whether this pod is being dispensed from a connection that operates on graphs.
     */
    public function formatModel($type, $isGraph)
    {
        return '\Paradox\GenericModel';
    }

}
