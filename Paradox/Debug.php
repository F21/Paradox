<?php
namespace Paradox;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Debug
 * The debugger provides debugging on the global basis. It prints the raw HTTP request that is sent to the server, and the raw HTTP response received.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Debug
{
    /**
     * Whether the debugger is on or off.
     * @var boolean
     */
    protected $_debug;

    /**
     * Sets up the debugger and turn the debugger on or off.
     * @param string $debug Set to turn the debugger on or off.
     */
    public function __construct($debug = false)
    {
        $this->_debug = (bool) $debug;
    }

    /**
     * Allows this object to be called as a function to call the trace method.
     * @param string $type Whether the debug message was for sending a request or receiving a response.
     * @param string $data The raw HTTP request or response.
     */
    public function __invoke($data)
    {
        $this->trace($data);
    }

    /**
     * Turns the debugger on or off.
     * @param boolean $value
     */
    public function setDebug($value)
    {
        $this->_debug = (bool) $value;
    }

    /**
     * Outputs the debug message.
     * @param \triagens\ArangoDb\TraceResponse|\triagens\ArangoDb\TraceRequest $data The HTTP request or response trace object.
     */
    protected function trace($data)
    {
        if ($this->_debug) {

            //Open the tags and output request specific items
            if ($data->getType() == "request") {
                print '<table style="border-style: solid; border-width: 1px; border-color: #b1b1b1; margin: 5px"><tr><td style="vertical-align: top; padding: 10px; width: 400px">';
                print "<h3>Request to server</h3>";
                print "<p><strong>{$data->getMethod()}</strong> {$data->getRequestUrl()}</p>";
            }

            //Output response specific items
            if ($data->getType() == "response") {
                print '<td style="vertical-align: top; padding: 10px; padding-left: 20px; width: 400px"><h3>Response from server</h3>';

                if ($data->getHttpCode() >= 400) {
                    print '<p style="color: red"><strong>' . $data->getHttpCode() . '</strong> ' . $data->getHttpCodeDefinition() . '</p>';
                } else {
                    print '<p style="color: green"><strong>' . $data->getHttpCode() . '</strong> ' . $data->getHttpCodeDefinition() . '</p>';
                }

            }

            //Headers
            print "<pre>";
            foreach ($data->getHeaders() as $header => $value) {
                 print "$header : $value\n";
            }
            print "</pre>";

            //Body
            if ($data->getBody()) {
                $body = json_decode($data->getBody());
                print "<pre>" . json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</pre>";
            }

            //Close off the tags
            if ($data->getType() == "request") {
                print '</td>';
            }

            if ($data->getType() == "response") {
                print "</td></tr></table>";
            }
        }
    }
}
