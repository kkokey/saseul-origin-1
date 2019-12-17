<?php

namespace Saseul\Script\Test;

use Saseul\Common\Script;
use Saseul\Data\Tracker;

class Ban extends Script
{
    function main()
    {
        $ask = $this->ask('address? ');

        Tracker::ban($ask);
    }
}
