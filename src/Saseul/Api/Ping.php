<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Constant\HttpStatus;
use Saseul\Constant\Signal;
use Saseul\Core\Property;

class Ping extends Api
{
    function main()
    {
        if (Property::daemonSig() !== Signal::RUN) {
            $msg = 'Service unavailable. ';
            $this->fail(HttpStatus::SERVICE_UNAVAILABLE, $msg);
        } else {
            $this->data = 'ok';
        }
    }
}