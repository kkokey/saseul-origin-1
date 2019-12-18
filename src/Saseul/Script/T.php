<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Constant\Directory;
use Saseul\Constant\Role;
use Saseul\Core\Env;
use Saseul\Core\IMLog;
use Saseul\Core\Property;
use Saseul\Daemon\Light;
use Saseul\Daemon\Node;
use Saseul\Daemon\Validator;
use Saseul\Data\Chunk;
use Saseul\Data\Tracker;
use Saseul\Util\Logger;

class T extends Script
{
    function main()
    {
        $a = Chunk::chunkList(Directory::API_CHUNKS);

        Logger::log($a);

        $a = 'S01';
        $b = 'S011';

        Logger::log($a > $b);
        Logger::log($b > $a);
    }
}
