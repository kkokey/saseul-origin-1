<?php

namespace Saseul\Service;

use Saseul\Constant\Role;
use Saseul\Core\Debugger;
use Saseul\Common\Service;
use Saseul\Constant\Signal;
use Saseul\Core\Env;
use Saseul\Core\IMLog;
use Saseul\Core\Process;
use Saseul\Core\Property;
use Saseul\Daemon\Light;
use Saseul\Daemon\Node;
use Saseul\Daemon\Validator;
use Saseul\Data\Tracker;
use Saseul\Util\Logger;

class Daemon extends Service
{
    function __construct()
    {
        $this->checkPid();
        $this->setPid();

        if (function_exists('gc_enable')) {
            gc_enable();
        }
    }

    function main()
    {
        Property::reset();

        while (true)
        {
            $this->check();
            IMLog::add(date('Y-m-d H:i:s').' Now Running ');
            $node = $this->node();
            $node->main();
            $this->iterate(10000);
            $this->reload();
        }
    }

    function check(): void
    {
        $check_mem = Env::checkMemcached();

        if (!$check_mem) {
            $msg = "Memcached is not running. ";

            Debugger::info($msg);
            Logger::debug($msg, true);
        }

        $check_mongo = Env::checkMongo();

        if (!$check_mongo) {
            $msg = "Memcached is not running. ";

            Debugger::info($msg);
            Logger::debug($msg, true);
        }
    }

    function node(): Node
    {
        $role = Tracker::getRole(Env::getAddress());

        switch ($role)
        {
            case Role::VALIDATOR:
                return Validator::GetInstance();
                break;
            default:
                return Light::GetInstance();
                break;
        }
    }

    function reload()
    {
        if (Property::daemonSig() === Signal::RELOAD) {
            Property::daemonSig(Signal::ZOMBIE);
            exit();
        }
    }

    function iterate(int $micro_seconds): void
    {
        usleep($micro_seconds);
        clearstatcache();

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    function checkPid(): void
    {
        if (Process::isRunning(Property::daemonPid())) {
            $msg = 'Daemon is already running. ';
            Debugger::info($msg);
            Logger::debug($msg, true); // exit;
        }
    }

    function setPid(): void
    {
        Property::daemonPid(getmypid());
    }
}
