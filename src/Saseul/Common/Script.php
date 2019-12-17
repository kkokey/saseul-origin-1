<?php

namespace Saseul\Common;

use Saseul\Util\Logger;

class Script
{
    protected $arg = [];
    protected $data = [];

    public function setArg($arg = [])
    {
        $this->arg = $arg;
    }

    public function exec()
    {
        $this->main();
        $this->result();
    }

    public function main()
    {
    }

    public function ask(string $msg): string
    {
        Logger::log(PHP_EOL . $msg);
        return trim(fgets(STDIN));
    }

    protected function result()
    {
        if ($this->data !== []) {
            Logger::log($this->data);
        }

        exit();
    }
}
