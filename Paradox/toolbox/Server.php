<?php
namespace Paradox\toolbox;
use Paradox\Toolbox;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Server functions
 * Manages server tasks, for example, creating and deleting graphs and collections and other management tasks.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Server
{
    /**
     * A reference to the toolbox.
     * @var Toolbox
     */
    private $_toolbox;

    /**
     * Instantiates the finder.
     * @param Toolbox $toolbox
     */
    public function __construct(Toolbox $toolbox)
    {
        $this->_toolbox = $toolbox;
    }
}
