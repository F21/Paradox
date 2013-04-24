<?php
/**
 * Test suite for Paradox.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */

//Setup the autoloader
require_once __DIR__ . "/../Paradox/Autoloader.php";
Paradox\Autoloader::init();

//Set up the autoloader for vendors
require_once __DIR__ . "/../vendor/autoload.php";

//Require the base
require_once __dir__ . "/Base.php";
