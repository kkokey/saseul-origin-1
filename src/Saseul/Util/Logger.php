<?php

namespace Saseul\Util;

/**
 * Logger provides functions for logging.
 */
class Logger
{
    /**
     * Prints information of an object and calling location.
     *
     * Optionally exit the program completely.
     *
     * @param mixed $obj    Object that need to print for inspect its value.
     * @param bool  $option When true, exit the program.
     */
    public static function debug($obj, $option = false)
    {
        if (function_exists('xdebug_call_file') && function_exists('xdebug_call_line')) {
            print_r('[Log] '. xdebug_call_file(). ':'. xdebug_call_line(). PHP_EOL);
        }

        self::log($obj);

        if ($option) {
            exit();
        }
    }

    /**
     * Prints information of an object.
     *
     * @param mixed $obj Object that need to print for inspect its value.
     */
    public static function log($obj)
    {
        print_r($obj);
        print_r(PHP_EOL);
    }

    /**
     * Prints an error message with an object and calling location.
     * And Exit the program completely.
     *
     * @param array $obj Object that need to print for inspect its value.
     */
    public static function error($obj = null)
    {
        print_r('[Error] ' . xdebug_call_file() . ':' . xdebug_call_line() . PHP_EOL);

        if ($obj !== null) {
            print_r($obj);
            print_r(PHP_EOL);
        }

        exit();
    }
}
