<?php
spl_autoload_register(function ($class_name) {
    if (!class_exists($class_name) && preg_match('/^(\\\?Saseul)/', $class_name)) {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        $filename = __DIR__.DIRECTORY_SEPARATOR."{$class}.php";

        if (file_exists($filename))
        {
            require_once($filename);
        }
    }
});