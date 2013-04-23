<?php
namespace Paradox\toolbox;
use \triagens\ArangoDb\Statement;
use \triagens\ArangoDb\Cursor;
use Paradox\Toolbox;
use Paradox\exceptions\QueryException;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Query helper
 * Provides a simple way to send AQL queries to the server and receive the response.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Query
{
    /**
     * A reference to the toolbox.
     * @var unknown
     */
    private $_toolbox;

    /**
     * Initiate the query helper.
     * @param Toolbox $toolbox
     */
    public function __construct(Toolbox $toolbox)
    {
        $this->_toolbox = $toolbox;
    }

    /**
     * Executes an AQL query and return all results. If nothing is found, an empty array is returned.
     * @param  string         $query      The AQL query to run.
     * @param  array          $parameters An optional associative array containing parameters to bind to the query.
     * @throws QueryException
     * @return array
     */
    public function getAll($query, array $parameters = array())
    {
        $data = array(
            'query' => $query,
            'bindVars' => $parameters,
             Cursor::ENTRY_FLAT => true
        );

        $statement = new Statement($this->_toolbox->getConnection(), $data);

        try {
            $cursor = $statement->execute();
        } catch (\triagens\ArangoDb\ServerException $e) {
            throw new QueryException($e->getServerMessage(), $e->getServerCode());
        }

        $results = $cursor->getAll();

        return $results;

    }

    /**
     * Executes an AQL query and return the first result. If nothing is found, null is returned.
     * @param  string         $query      The AQL query to run.
     * @param  array          $parameters An optional associative array containing parameters to bind to the query.
     * @throws QueryException
     * @return return         AModel
     */
    public function getOne($query, array $parameters = array())
    {
        $data = array(
            'query' => $query,
            'bindVars' => $parameters,
             Cursor::ENTRY_FLAT => true
        );

        $statement = new Statement($this->_toolbox->getConnection(), $data);

        try {
            $cursor = $statement->execute();
        } catch (\triagens\ArangoDb\ServerException $e) {
            throw new QueryException($e->getServerMessage(), $e->getServerCode());
        }

        if ($cursor->valid()) {
            $cursor->rewind();

            return $cursor->current();
        } else {
            return null;
        }
    }

    /**
     * Returns the execution plan for a query. This will not execute the query.
     * @param  string         $query      The AQL query to run.
     * @param  array          $parameters An optional associative array containing parameters to bind to the query.
     * @throws QueryException
     * @return array
     */
    public function explain($query, $parameters)
    {
        $data = array(
                'query' => $query,
                'bindVars' => $parameters
        );

        $statement = new Statement($this->_toolbox->getConnection(), $data);

        try {
            return $statement->explain();
        } catch (\triagens\ArangoDb\ServerException $e) {
            throw new QueryException($e->getServerMessage(), $e->getServerCode());
        }
    }
}
