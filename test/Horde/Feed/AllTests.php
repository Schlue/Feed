<?php
/**
 * @package    Horde_Feed
 * @subpackage UnitTests
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Feed_AllTests::main');
}

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

class Horde_Feed_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        // Set up autoload
        set_include_path(dirname(__FILE__) . '/../../../lib' . PATH_SEPARATOR . get_include_path());
        if (!spl_autoload_functions()) {
            spl_autoload_register(create_function('$class', '$filename = str_replace(array(\'\\\\\', \'_\'), \'/\', $class); include "$filename.php";'));
        }

        $suite = new PHPUnit_Framework_TestSuite('Horde Framework - Horde_Feed');

        $basedir = dirname(__FILE__);
        $baseregexp = preg_quote($basedir . DIRECTORY_SEPARATOR, '/');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basedir)) as $file) {
            if ($file->isFile() && preg_match('/Test.php$/', $file->getFilename())) {
                $pathname = $file->getPathname();
                require $pathname;

                $class = str_replace(DIRECTORY_SEPARATOR, '_',
                                     preg_replace("/^$baseregexp(.*)\.php/", '\\1', $pathname));
                $suite->addTestSuite('Horde_Feed_' . $class);
            }
        }

        return $suite;
    }

}

if (PHPUnit_MAIN_METHOD == 'Horde_Feed_AllTests::main') {
    Horde_Feed_AllTests::main();
}
