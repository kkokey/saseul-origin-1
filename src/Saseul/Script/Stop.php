<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Constant\Signal;
use Saseul\Core\Property;
use Saseul\Util\Logger;

class Stop extends Script
{
    function main()
    {
        Logger::log('Kill Daemon ... ');
        Property::daemonSig(Signal::KILL);
        Logger::log('OK. ');
    }
}
