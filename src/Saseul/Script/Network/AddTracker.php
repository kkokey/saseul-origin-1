<?php

namespace Saseul\Script\Network;

use Saseul\Common\Script;
use Saseul\Core\Debugger;
use Saseul\Data\Tracker;
use Saseul\Util\Logger;

class AddTracker extends Script
{
    function main()
    {
        $host = $this->ask('Please type host to add. ');
        Tracker::registerRequest($host);

        $msg = 'Register request success: '.$host;
        Debugger::info($msg);
        Logger::log($msg);
    }
}
