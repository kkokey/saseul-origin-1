<?php

namespace Saseul\Committer;

use Saseul\Common\Request;
use Saseul\Constant\Directory;
use Saseul\Util\File;

class RequestManager
{
    protected static $instance = null;

    public $requests;
    public $request;

    public function __construct()
    {
        $this->requests = [];
        $this->request = new Request();

        $this->loadSystemRequests();
        $this->loadCustomRequests();
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function loadSystemRequests()
    {
        $dir = Directory::SYSTEM_REQUEST;

        $requests = File::getFiles($dir, Directory::SRC);
        $requests = preg_replace('/\.php$/', '', $requests);

        foreach ($requests as $request) {
            $class = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $request);
            $this->requests[$class::TYPE] = new $class();
        }
    }

    public function loadCustomRequests()
    {
        $dir = Directory::CUSTOM_REQUEST;

        $requests = File::getFiles($dir, Directory::CUSTOM_REQUEST);
        $requests = preg_replace('/\.php$/', '', $requests);

        foreach ($requests as $request) {
            $filename = Directory::CUSTOM_REQUEST.DIRECTORY_SEPARATOR.$request.'.php';
            $class = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $request);
            $class = 'Custom\\Request\\'.$class;

            if (file_exists($filename) && !class_exists($class)) {
                require_once($filename);
            }

            if (class_exists($class) && !isset($this->requests[$class::TYPE])) {
                $this->requests[$class::TYPE] = new $class();
            }
        }
    }

    public function initializeRequest($type, $request, $rhash, $public_key, $signature): void
    {
        if (isset($this->requests[$type])) {
            $this->request = $this->requests[$type];
        }

        $this->request->_init($request, $rhash, $public_key, $signature);
    }

    public function getRequestValidity(): bool
    {
        return $this->request->_getValidity();
    }

    public function getResponse(): array
    {
        return $this->request->_getResponse();
    }

    public function getMessage()
    {
        return $this->request->_getMessage();
    }
}
