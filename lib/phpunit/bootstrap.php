<?php
define('TESTSRUNNING', 1);
define('CLI_SCRIPT', 1);
global $CFG;

require_once(dirname(__FILE__).'/../../config.php');


class UnitTestCase extends PHPUnit_Framework_TestCase {};
