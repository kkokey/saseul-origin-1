<?php

namespace Saseul\Daemon;

use Saseul\Constant\Role;
use Saseul\Core\Property;

class Arbiter extends Node
{
    protected static $instance;

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function main()
    {
        Property::role(Role::ARBITER);
        parent::main();
    }
}