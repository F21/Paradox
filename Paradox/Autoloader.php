<?php
namespace Paradox;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Autoloader
 * A simple script to autoload the class files for the library. The project is structured to be PSR-0 compliant, so you can also use your own
 * autoloader.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Autoloader
{
    /**
     * Class file extension.
     */
    const EXTENSION = '.php';

    private static $dirRoot = null;

    /**
     * Initialise the autoloader by registering it.
     * @return void
     */
    public static function init()
    {
        spl_autoload_register(__NAMESPACE__ . '\Autoloader::load');

        self::$dirRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR;
    }

    /**
     * Search for the class's file and require it.
     * @param string $className The name of the class.
     */
    public static function load($className)
    {
        $className = str_replace(__NAMESPACE__, '', $className);
        $className = str_replace("\\", DIRECTORY_SEPARATOR, $className);

        if (file_exists(self::$dirRoot . $className . self::EXTENSION)) {
            require_once self::$dirRoot . $className . self::EXTENSION;
        }
    }
}
