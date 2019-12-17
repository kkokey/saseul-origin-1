<?php

namespace Saseul\Service;

use Saseul\Core\Debugger;
use Saseul\Common\Service;
use Saseul\Constant\Directory;
use Saseul\Constant\Signal;
use Saseul\Core\Process;
use Saseul\Core\Property;
use Saseul\Data\Tracker;
use Saseul\Util\Logger;

class DaemonLoader extends Service
{
    private $renew_count = 50;
    private $exclude_reset_count = 300;

    function __construct()
    {
        $this->checkLoaderPID();
        $this->setLoaderPID();
        if (function_exists('gc_enable')) {
            gc_enable();
        }
    }

    function main()
    {
        Property::init();

        while (true) {
            $this->manage();
            $this->iterate(100000); # 0.1 seconds
        }
    }

    function checkPeers(): void
    {
        if ($this->renew_count > 50) {
            $this->renewPeers();
            $this->renew_count = 0;
        }

        if ($this->exclude_reset_count > 300) {
            Property::excludeRequest([]);
            $this->exclude_reset_count = 0;
        }

        $this->renew_count++;
        $this->exclude_reset_count++;
    }

    function renewPeers(): void
    {
        $r = Property::registerRequest();
        $p = Tracker::pullPeerHosts();
        $k = Tracker::getKnownHosts(array_merge($r, $p));

        $hosts = array_unique(
            array_diff(
                array_merge($r, $p, $k), Property::excludeRequest()
            )
        );

        Property::registerRequest([]);
        Tracker::checkPeers($hosts);
    }

    function manage(): void
    {
        if (!is_numeric(Property::daemonSig())) {
            return;
        }

        switch (Property::daemonSig())
        {
            case Signal::RUN:
                $this->checkPeers();
                $this->createDaemon();
                break;
            case Signal::RESTART:
                $this->restartDaemon();
                $this->renew_count = 50;
                break;
            case Signal::ZOMBIE:
                $this->clearDaemon();
                break;
            case Signal::STOP:
                $this->stopDaemon();
                break;
            case Signal::KILL:
                $this->killAll();
                break;
        }
    }

    function stopDaemon(): void
    {
        Process::destroy(Property::daemonPid());
        $this->iterate(500000); # 0.5 seconds
        Process::killDefunct(Property::daemonPid());
    }

    function clearDaemon(): void
    {
        Process::killDefunct(Property::daemonPid());
        Property::daemonSig(Signal::RUN);
    }

    function createDaemon(): void
    {
        if (!Process::isRunning(Property::daemonPid())) {
            Process::spawn('Daemon');
            $this->iterate(5000000); # 5 seconds
        }
    }

    function restartDaemon(): void
    {
        Process::destroy(Property::daemonPid());
        $this->iterate(500000); # 0.5 seconds
        Process::killDefunct(Property::daemonPid());
        Property::daemonSig(Signal::RUN);
    }

    function killAll(): void
    {
        Process::destroy(Property::daemonPid());
        $this->iterate(500000); # 0.5 seconds
        Property::destroy();

        if (is_file(Directory::SASEULD_PID_FILE)) {
            unlink(Directory::SASEULD_PID_FILE);
        }

        exit();
    }

    function iterate(int $micro_seconds): void
    {
        usleep($micro_seconds);
        clearstatcache();

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    function checkLoaderPID(): void
    {
        if (!is_file(Directory::SASEULD_PID_FILE)) {
            return;
        }

        $pid = file_get_contents(Directory::SASEULD_PID_FILE);

        if (Process::isRunning($pid)) {
            $msg = 'Daemon is already running. ';
            Debugger::info($msg);
            Logger::debug($msg, true); // exit;
        }
    }

    function setLoaderPID(): void
    {
        if (!file_put_contents(Directory::SASEULD_PID_FILE, getmypid())) {
            $msg = 'Unable to write pidfile';
            Debugger::info($msg);
            Logger::debug($msg, true);
        }
    }
}
