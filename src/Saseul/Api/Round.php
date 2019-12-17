<?php
namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Property;
use Saseul\Core\Rule;
use Saseul\Data\Block;
use Saseul\Data\Tracker;

class Round extends Api
{
    function main()
    {
        $round_number = (int)$this->getParam($_REQUEST, 'round_number', ['default' => 0]);
        $register = $this->getParam($_REQUEST, 'register', ['default' => 0]);
        $last_block = Block::getByNumber($round_number - 1);
        $last_blockhash = $last_block['blockhash'];
        $round_key = '';

        if ($last_blockhash !== '' || $round_number === 1) {
            $round_key = Rule::roundKey($last_blockhash, $round_number);
        }

        if ((int)$register === 1)
        {
            $host = $this->getParam($_REQUEST, 'host', ['default' => $_SERVER['REMOTE_ADDR']]);
            Tracker::registerRequest($host);
        }

        $this->data = [
            'round' => Property::round(),
            'key' => $round_key,
        ];
    }
}