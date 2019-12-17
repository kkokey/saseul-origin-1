<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Constant\HttpStatus;

class NotFound extends Api
{
    function main()
    {
        $msg = 'Api not found. ';
        $msg.= 'Please check request uri ';

        $this->fail(HttpStatus::NOT_FOUND, $msg);
    }
}