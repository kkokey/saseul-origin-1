<?php

namespace Saseul\Util;

class Parser
{
    public static function objectToArray($d)
    {
        if (is_object($d)) {
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            return array_map('self::' . __FUNCTION__, $d);
        }

        return $d;
    }

    public static function arrayToObject($d)
    {
        if (is_array($d)) {
            return (object) array_map('self::' . __FUNCTION__, $d);
        }

        return $d;
    }

    public static function escapeArg($obj)
    {
        return preg_replace('/[^A-Za-z0-8_\-\\'.DIRECTORY_SEPARATOR.']/', '', $obj);
    }

    public static function findMostItem(array $array, string $key, array $exclude = [])
    {
        $cnt = [];

        foreach ($array as $item) {
            if (!isset($cnt[$item[$key]])) {
                $cnt[$item[$key]] = 1;
            } else {
                $cnt[$item[$key]] = $cnt[$item[$key]] + 1;
            }
        }

        if (count($cnt) > 1) {
            $k = array_search(max(array_values($cnt)), $cnt);

            foreach ($array as $item) {
                if ($item[$key] === $k) {
                    return [
                        'unique' => false,
                        'item' => $item,
                    ];
                }
            }
        }

        $item = $array[0] ?? [];

        return [
            'unique' => true,
            'item' => $item,
        ];
    }
}
