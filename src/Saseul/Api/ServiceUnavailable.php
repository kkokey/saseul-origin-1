<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Constant\HttpStatus;

class ServiceUnavailable extends Api
{
    function main()
    {
        $msg = 'Service unavailable. ';
        $this->fail(HttpStatus::SERVICE_UNAVAILABLE, $msg);
    }
}