<?php
# Internal message log

namespace Saseul\Core;

class IMLog
{
    static function add($log)
    {
        $iMLogProperty = Property::iMLog();

        if (!is_array($iMLogProperty)) {
            $iMLogProperty = [];
        }

        $iMLogProperty[] = $log;

        if (count($iMLogProperty) > 30)
        {
            unset($iMLogProperty[0]);
            $iMLogProperty = array_values($iMLogProperty);
        }

        Property::iMLog($iMLogProperty);
//        Debugger::info($log);
    }

    static function reset()
    {
        Property::iMLog([]);
    }

    static function get()
    {
        $iMLogProperty = Property::iMLog();

        if (empty($iMLogProperty)) {
            return '';
        }

        return self::parse($iMLogProperty);
    }

    static function parse(array $iMLogProperty)
    {
        $str = '';

        foreach ($iMLogProperty as $row)
        {
            if (!is_string($row)) {
                continue;
            }

            $str.= $row . PHP_EOL;
        }

        return $str;
    }
}