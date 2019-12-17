<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Constant\Role;
use Saseul\Core\Env;
use Saseul\Core\IMLog;
use Saseul\Core\Property;
use Saseul\Daemon\Light;
use Saseul\Daemon\Node;
use Saseul\Daemon\Validator;
use Saseul\Data\Tracker;

class Test extends Script
{
    function main()
    {
        Property::reset();

        IMLog::add(date('Y-m-d H:i:s').' Test Running... ');
        $node = $this->node();
        $node->main();
    }

    function node(): Node
    {
        $role = Tracker::getRole(Env::getAddress());

        switch ($role)
        {
            case Role::VALIDATOR:
                return Validator::GetInstance();
                break;
            default:
                return Light::GetInstance();
                break;
        }
    }
}
