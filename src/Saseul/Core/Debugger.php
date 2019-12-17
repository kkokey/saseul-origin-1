<?php

namespace Saseul\Core;

use Saseul\Constant\Directory;

class Debugger
{
    public static function info($obj)
    {
        $info = '['. date('Y-m-d H:i:s'). '] ';
        $file = Directory::DEBUG_LOG_FILE;

        if (function_exists('xdebug_call_file') && function_exists('xdebug_call_line')) {
            $info.= xdebug_call_file(). ':'. xdebug_call_line(). PHP_EOL;
        }

        if (is_string($obj) || is_numeric($obj)) {
            $info.= $obj. PHP_EOL;
        } else if (is_bool($obj) || is_array($obj) || is_object($obj)){
            $info.= json_encode($obj). PHP_EOL;
        }

        $f = fopen($file, 'a');
        fwrite($f, $info);
        fclose($f);
    }
}