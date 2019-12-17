<?php

namespace Saseul\Daemon;

use Saseul\Consensus\HAP;
use Saseul\Core\Property;

class Node
{
    protected static $instance;
    protected $consensus;

    function __construct()
    {
        $this->consensus = HAP::GetInstance();
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function main()
    {
        $this->consensus->init();
        $this->consensus->round();

        $round_info = Property::roundInfo();
        $my_round_number = (int) $round_info['my_round_number'];
        $net_round_number = (int) $round_info['net_round_number'];

        if ($my_round_number < $net_round_number)
        {
            $this->consensus->sync();
        }

        $this->consensus->finishingWork();
    }
}