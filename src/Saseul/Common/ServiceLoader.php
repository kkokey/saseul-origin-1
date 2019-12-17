<?php

namespace Saseul\Common;

use Saseul\Constant\Directory;
use Saseul\Core\Debugger;
use Saseul\Core\Env;
use Saseul\Util\Logger;
use Saseul\Util\Parser;

class ServiceLoader
{
    private $argv;

    public function __construct($argv)
    {
        $this->argv = Parser::escapeArg($argv);

        if (!isset($this->argv[1])) {
            $this->argv[1] = '';
        }
    }

    public function main(): void
    {
        $env_load = Env::load();

        if ($env_load === false) {
            $msg = "Env file load failed. Run saseul_script for set up env file. ";

            Debugger::info($msg);
            Logger::debug($msg, true);
        }

//        Env::registerErrorHandler();
        Env::apply();
        $service = $this->route($this->argv[1]);

        if ($service === '') {
            $msg = "There is no service code. Invalid service name.";

            Debugger::info($msg);
            Logger::debug($msg, true);
        } else {
            $this->execService($service);
        }
    }

    public function route(string $arg): string
    {
        $service_file = Directory::SERVICE.DIRECTORY_SEPARATOR.$arg;

        if (is_file($service_file.'.php')) {
            return $arg;
        }

        return '';
    }

    public function execService($service)
    {
        $prefix = str_replace(Directory::SRC.DIRECTORY_SEPARATOR, '', Directory::SERVICE);
        $service = $prefix.DIRECTORY_SEPARATOR.$service;
        $service = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $service);
        $service = preg_replace('/\\\\{2,}/', '\\', $service);

        $arg = [];

        if (count($this->argv) > 2) {
            $arg = $this->argv;
            unset($arg[0]);
            unset($arg[1]);

            $arg = array_values($arg);
        }

        $target = new $service();
        $target->setArg($arg);
        $target->main();
    }
}
