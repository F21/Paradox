<?php
namespace Paradox;

use Paradox\pod\Document;
/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Default model formatter
 * The default model formatter that just asks the library to instantiate \Paradox\GenericModel for everything.
 *
 * @version 1.3.0
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class DefaultModelFormatter implements IModelFormatter
{
    /**
     * Returns \Paradox\GenericModel for all types.
     * @param Document $pod	The document pod.
     * @param boolean $isGraph Whether this pod is being dispensed from a connection that operates on graphs.
     */
    public function formatModel($pod, $isGraph)
    {
        return '\Paradox\GenericModel';
    }

}
