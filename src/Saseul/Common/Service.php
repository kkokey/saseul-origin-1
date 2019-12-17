<?php

namespace Saseul\Common;

class Service
{
    protected $arg = [];

    public function setArg($arg = [])
    {
        $this->arg = $arg;
    }

    public function main() {}
}
