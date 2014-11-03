<?php
namespace tests;
use \Paradox\Client;

/**
 * Base class for the Paradox test suite.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Base extends \PHPUnit_Framework_TestCase
{
    /**
     * Get an instance of the Paradox client.
     * @param  string          $endpoint The address of the server.
     * @param  string          $username The username.
     * @param  string          $password The password.
     * @param  string          $graph    The optional name of the graph to manage.
     * @param  string          $database The optional database to use.
     * @return \Paradox\Client
     */
    protected function getClient($endpoint = 'tcp://localhost:8529', $username = 'root', $password = '', $graph = null, $database = null)
    {
        return $client = new Client($endpoint, array('username' => $username, 'password' => $password, 'graph' => $graph, 'database' => $database));
    }

    protected function getDefaultEndpoint()
    {
        return 'tcp://localhost:8529';
    }

    protected function getDefaultUsername()
    {
        return 'root';
    }

    protected function getDefaultPassword()
    {
        return '';
    }
}
