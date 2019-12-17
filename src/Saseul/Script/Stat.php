<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\Property;
use Saseul\Util\Logger;

class Stat extends Script
{
    public function main()
    {
        Logger::log(Property::getAll());
    }
}
