<?php

namespace Saseul\Committer;

use Saseul\Common\Contract;
use Saseul\Constant\Directory;
use Saseul\Util\File;

class ContractManager
{
    public $contracts;
    public $contract;

    public function __construct()
    {
        $this->contracts = [];
        $this->contract = new Contract();

        $this->loadSystemContracts();
        $this->loadCustomContracts();
    }

    public function loadSystemContracts()
    {
        $dir = Directory::SYSTEM_CONTRACT;

        $contracts = File::getFiles($dir, Directory::SRC);
        $contracts = preg_replace('/\.php$/', '', $contracts);

        foreach ($contracts as $contract) {
            $class = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $contract);
            $this->contracts[$class::TYPE] = new $class();
        }
    }

    public function loadCustomContracts()
    {
        $dir = Directory::CUSTOM_CONTRACT;

        $contracts = File::getFiles($dir, Directory::CUSTOM_CONTRACT);
        $contracts = preg_replace('/\.php$/', '', $contracts);

        foreach ($contracts as $contract) {
            $filename = Directory::CUSTOM_CONTRACT.DIRECTORY_SEPARATOR.$contract.'.php';
            $class = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $contract);
            $class = 'Custom\\Contract\\'.$class;

            if (file_exists($filename) && !class_exists($class)) {
                require_once($filename);
            }

            if (class_exists($class) && !isset($this->contracts[$class::TYPE])) {
                $this->contracts[$class::TYPE] = new $class();
            }
        }
    }

    public function initTransaction($type, $transaction, $thash, $public_key, $signature)
    {
        if (isset($this->contracts[$type])) {
            $this->contract = $this->contracts[$type];
        }

        $this->contract->_init($transaction, $thash, $public_key, $signature);
    }

    public function getTransactionValidity()
    {
        return $this->contract->_getValidity();
    }

    public function loadStatus()
    {
        $this->contract->_loadStatus();
    }

    public function getStatus()
    {
        $this->contract->_getStatus();
    }

    public function makeDecision()
    {
        return $this->contract->_makeDecision();
    }

    public function setStatus()
    {
        $this->contract->_setStatus();
    }
}
