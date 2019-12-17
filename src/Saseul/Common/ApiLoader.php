<?php

namespace Saseul\Common;

use Saseul\Api\NotFound;
use Saseul\Constant\Directory;
use Saseul\Constant\Signal;
use Saseul\Core\Env;
use Saseul\Core\Property;

class ApiLoader
{
    public function __construct()
    {
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') > -1) {
            $_POST = json_decode(file_get_contents('php://input'), true);
            $_REQUEST = empty($_POST) ? $_REQUEST : array_merge($_REQUEST, $_POST);
        }
    }

    public function getUri()
    {
        if (!empty($_REQUEST['handler'])) {
            return $_REQUEST['handler'];
        }
        if (!empty($_SERVER['REQUEST_URI'])) {
            return $_SERVER['REQUEST_URI'];
        }

        return null;
    }

    public function main(): void
    {
        $env_load = Env::load();
        $check_mem = Env::checkMemcached();
        $check_mongo = Env::checkMongo();

        if (!$env_load || !$check_mem || !$check_mongo ||
            !Signal::isAlive(Property::daemonSig()))
        {
            $uri = '/serviceunavailable';
        } else {
            $uri = $this->getUri();

            if ($uri === '/') {
                $uri = '/main';
            }

            if ($uri === null) {
                $uri = '/notfound';
            }
        }

        $this->route($uri);
    }

    private function route($uri): void
    {
        $apiName = ltrim(parse_url($uri)['path'], '/');
        $apiClassName = $this->getApiClassName($apiName);

        if ($apiClassName === '' || !class_exists($apiClassName)) {
            $api = new NotFound();
        } else {
            $api = new $apiClassName();
        }

        $api->exec();
    }

    private function getApiClassName(string $apiName): string
    {
        $parent = Directory::API;
        $target = $apiName;

        if (!preg_match('/\.php$/', $target)) {
            $target = "{$target}.php";
        }

        $dir = explode('/' , $target);
        $apiClassName = '';

        foreach ($dir as $item) {
            $child = $this->getChildName($parent, $item);

            if ($child === '') {
                return '';
            }

            $parent = $parent.DIRECTORY_SEPARATOR.$child;
            $apiClassName = "{$apiClassName}/{$child}";
        }

        $apiClassName = preg_replace('/\.php$/', '', $apiClassName);
        $apiClassName = preg_replace('/\\/{1,}/', '\\', $apiClassName);
        $apiClassName = "Saseul\\Api{$apiClassName}";

        return $apiClassName;
    }

    private function getChildName(string $parent, string $child): string
    {
        if (is_dir($parent)) {
            $dir = scandir($parent);

            foreach ($dir as $item) {
                if (strtolower($child) === strtolower($item)) {
                    return $item;
                }
            }
        }

        return '';
    }
}