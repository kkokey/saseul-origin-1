<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\Process;
use Saseul\Util\Logger;

class Start extends Script
{
    function main()
    {
        Logger::log('Start Daemon ... ');
        Process::spawn('DaemonLoader');
        Logger::log('OK. ');
    }
}
