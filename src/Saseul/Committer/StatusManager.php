<?php

namespace Saseul\Committer;

use Saseul\Constant\Directory;
use Saseul\Util\File;

class StatusManager
{
    public $statuses;

    public function __construct()
    {
        $this->loadSystemStatus();
        $this->loadCustomStatus();
    }

    public function loadSystemStatus()
    {
        $dir = Directory::SYSTEM_STATUS;

        $statuses = File::getFiles($dir, Directory::SRC);
        $statuses = preg_replace('/\.php$/', '', $statuses);

        foreach ($statuses as $status) {
            $class = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $status);
            $this->statuses[$status] = $class::GetInstance();
            $this->statuses[$status]->_setup();
        }
    }

    public function loadCustomStatus()
    {
        $dir = Directory::CUSTOM_STATUS;

        $statuses = File::getFiles($dir, Directory::CUSTOM_STATUS);
        $statuses = preg_replace('/\.php$/', '', $statuses);

        foreach ($statuses as $status) {
            $filename = Directory::CUSTOM_STATUS.DIRECTORY_SEPARATOR.$status.'.php';
            $class = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $status);
            $class = 'Custom\\Status\\'.$class;

            if (file_exists($filename) && !class_exists($class)) {
                require_once($filename);
            }

            if (class_exists($class) && !isset($this->statuses[$status])) {
                $this->statuses[$status] = $class::GetInstance();
                $this->statuses[$status]->_setup();
            }
        }
    }

    public function reset()
    {
        foreach ($this->statuses as $status) {
            $status->_reset();
        }
    }

    public function preprocess()
    {
        foreach ($this->statuses as $status) {
            $status->_preprocess();
        }
    }

    public function load()
    {
        foreach ($this->statuses as $status) {
            $status->_load();
        }
    }

    public function save()
    {
        foreach ($this->statuses as $status) {
            $status->_save();
        }
    }

    public function postprocess()
    {
        foreach ($this->statuses as $status) {
            $status->_postprocess();
        }
    }
}
