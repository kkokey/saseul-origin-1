<?php

namespace Saseul\Daemon;

use Saseul\Constant\Role;
use Saseul\Core\Property;

class Validator extends Node
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
        Property::role(Role::VALIDATOR);

        $this->consensus->init();
        $this->consensus->round();

        $round_info = Property::roundInfo();
        $my_round_number = (int) $round_info['my_round_number'];
        $net_round_number = (int) $round_info['net_round_number'];

        # consensus;
        if ($my_round_number === $net_round_number)
        {
            $this->consensus->networking();

        }
        else if ($my_round_number < $net_round_number)
        {
            $this->consensus->sync();
        }

        $this->consensus->finishingWork();
    }
}