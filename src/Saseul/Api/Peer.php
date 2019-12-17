<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Data\Tracker;

class Peer extends Api
{
    function main()
    {
        $this->data = Tracker::getPeers();
    }
}