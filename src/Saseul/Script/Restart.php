<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Constant\Signal;
use Saseul\Core\Property;
use Saseul\Util\Logger;

class Restart extends Script
{
    function main()
    {
        Logger::log('Restart Daemon ... ');
        Property::daemonSig(Signal::RESTART);
        Logger::log('OK. ');
    }
}
