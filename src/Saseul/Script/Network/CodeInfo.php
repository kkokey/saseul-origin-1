<?php

namespace Saseul\Script\Network;

use Saseul\Common\Script;
use Saseul\Constant\Directory;
use Saseul\Util\File;
use Saseul\Util\Logger;

class CodeInfo extends Script
{
    function main()
    {
        $system_contracts = $this->loadSystemContracts();
        $custom_contracts = $this->loadCustomContracts();
        $system_requests = $this->loadSystemRequests();
        $custom_requests = $this->loadCustomRequests();

        Logger::log('');
        Logger::log('[System Contracts]');

        foreach ($system_contracts as $contract)
        {
            Logger::log(' - '.$contract);
        }

        Logger::log('');
        Logger::log('[Custom Contracts]');

        foreach ($custom_contracts as $cid => $contract)
        {
            Logger::log(' - '.$cid.': '.$contract);
        }

        Logger::log('');

        Logger::log('[Available Requests]');

        foreach ($system_requests as $request)
        {
            Logger::log(' - '.$request);
        }

        foreach ($custom_requests as $request)
        {
            Logger::log(' - '.$request);
        }

        Logger::log('');
    }

    public function loadSystemContracts()
    {
        $dir = Directory::SYSTEM_CONTRACT;

        $contracts = File::getFiles($dir, Directory::SRC);
        $contracts = preg_replace('/\.php$/', '', $contracts);

        $items = [];

        foreach ($contracts as $contract) {
            $class = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $contract);
            $items[] = $class::TYPE;
        }

        return $items;
    }

    public function loadCustomContracts()
    {
        $dir = Directory::CUSTOM_CONTRACT;

        $contracts = File::getFiles($dir, Directory::CUSTOM_CONTRACT);
        $contracts = preg_replace('/\.php$/', '', $contracts);

        $items = [];

        foreach ($contracts as $contract) {
            $filename = Directory::CUSTOM_CONTRACT.DIRECTORY_SEPARATOR.$contract.'.php';
            $class = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $contract);
            $cid = $class;
            $class = 'Custom\\Contract\\'.$class;

            if (file_exists($filename) && !class_exists($class)) {
                require_once($filename);
            }

            if (class_exists($class) && !in_array($class::TYPE, $items)) {
                $items[$cid] = $class::TYPE;
            }
        }

        return $items;
    }

    public function loadSystemRequests()
    {
        $dir = Directory::SYSTEM_REQUEST;

        $requests = File::getFiles($dir, Directory::SRC);
        $requests = preg_replace('/\.php$/', '', $requests);

        $items = [];

        foreach ($requests as $request) {
            $class = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $request);
            $items[] = $class::TYPE;
        }

        return $items;
    }

    public function loadCustomRequests()
    {
        $dir = Directory::CUSTOM_REQUEST;

        $requests = File::getFiles($dir, Directory::CUSTOM_REQUEST);
        $requests = preg_replace('/\.php$/', '', $requests);

        $items = [];

        foreach ($requests as $request) {
            $filename = Directory::CUSTOM_REQUEST.DIRECTORY_SEPARATOR.$request.'.php';
            $class = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $request);
            $class = 'Custom\\Request\\'.$class;

            if (file_exists($filename) && !class_exists($class)) {
                require_once($filename);
            }

            if (class_exists($class) && !isset($this->requests[$class::TYPE])) {
                $items[] = $class::TYPE;
            }
        }

        return $items;
    }
}
