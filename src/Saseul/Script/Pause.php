<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Constant\Signal;
use Saseul\Core\Process;
use Saseul\Core\Property;
use Saseul\Util\Logger;

class Pause extends Script
{
    function main()
    {
        Logger::log('Stop Daemon ... ');
        Property::daemonSig(Signal::STOP);
        Logger::log('OK. ');
    }
}
