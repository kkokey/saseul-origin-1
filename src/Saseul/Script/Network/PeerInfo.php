<?php

namespace Saseul\Script\Network;

use Saseul\Common\Script;
use Saseul\Data\Tracker;
use Saseul\Util\Logger;

class PeerInfo extends Script
{
    function main()
    {
        $peers = Tracker::getPeers([]);
        $validators = Tracker::getValidatorAddress();

        Logger::log('');
        Logger::log('[Summary]');
        Logger::log(' - Peer count: '. count($peers));
        Logger::log(' - Validator count: '. count($validators));

        Logger::log('');
        Logger::log('[Peer Info]');

        foreach ($peers as $peer)
        {
            $str = ' - '.$peer['host']. ' / '. $peer['address'];

            if (in_array($peer['address'], $validators)) {
                $str.= ' / validator ';
            }

            Logger::log($str);
        }

        Logger::log('');
    }
}
