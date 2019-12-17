<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Property;

class HashInfo extends Api
{
    function main()
    {
        $round_key = $this->getParam($_REQUEST, 'round_key');

        $this->data = Property::hashInfo($round_key);
    }
}