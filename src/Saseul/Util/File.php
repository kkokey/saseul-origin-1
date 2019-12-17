<?php

namespace Saseul\Util;

class File
{
    public static function rrmdir($dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.','..']);

        foreach ($files as $file) {
            if (is_dir($dir.DIRECTORY_SEPARATOR.$file)) {
                self::rrmdir($dir.DIRECTORY_SEPARATOR.$file);
            } else {
                unlink($dir.DIRECTORY_SEPARATOR.$file);
            }
        }

        return rmdir($dir);
    }

    public static function mmkdir($dir, $mode): bool
    {
        # only posix
        if (extension_loaded('posix')) {
            if (!posix_access($dir, POSIX_W_OK)) {
                return false;
            }
        }

        return mkdir($dir, $mode);
    }

    public static function getAllFiles($dir)
    {
        $files = [];

        if (is_dir($dir)) {
            $contents = glob($dir.DIRECTORY_SEPARATOR.'*');

            foreach ($contents as $item) {
                if (is_file($item)) {
                    $files[] = $item;
                }

                if (is_dir($item)) {
                    $files = array_merge($files, self::getAllFiles($item));
                }
            }
        }

        sort($files);

        return $files;
    }

    public static function getFiles($full_dir, $del_dir = '')
    {
        $files = [];

        if (!is_dir($full_dir)) {
            return $files;
        }

        $d = scandir($full_dir);

        foreach ($d as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            if (is_dir($full_dir.DIRECTORY_SEPARATOR.$dir)) {
                $c_scripts = self::getFiles($full_dir.DIRECTORY_SEPARATOR.$dir, $del_dir);
                $files = array_merge($files, $c_scripts);
            } else {
                $escaped_del_dir = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\\\'.DIRECTORY_SEPARATOR, $del_dir);
                $files[] = preg_replace('/^'.$escaped_del_dir.'\\'.DIRECTORY_SEPARATOR.'/', '', $full_dir.DIRECTORY_SEPARATOR.$dir);
            }
        }

        return $files;
    }
}