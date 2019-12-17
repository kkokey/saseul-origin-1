<?php

namespace Saseul\Script\Test;

use Saseul\Common\Script;
use Saseul\Core\Env;
use Saseul\Core\Key;
use Saseul\Core\Rule;
use Saseul\Data\Tracker;
use Saseul\Util\DateTime;
use Saseul\Util\Logger;
use Saseul\Util\RestCall;
use Saseul\Version;

class SendCoin extends Script
{
    function main()
    {
        $validator = Tracker::getRandomValidator();
        $host = $validator['host'] ?? '';
        $rest = RestCall::GetInstance();

        $items = [];
        $items[] = $this->item1();

        foreach ($items as $item) {
            $rs = $rest->post('http://'.$host.'/transaction', $item);
            Logger::log($rs);
        }
    }

    function item1()
    {
        $type = 'SendCoin';
        $transaction = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => Env::getAddress(),
            'to' => Env::getAddress(),
            'timestamp' => DateTime::microtime() + Rule::MICROINTERVAL_OF_CHUNK,
            'amount' => '6'
        ];

        $thash = Rule::hash($transaction);
        $private_key = Env::getPrivateKey();
        $public_key = Env::getPublicKey();
        $signature = Key::makeSignature($thash, $private_key, $public_key);

        $item = [
            'transaction' => json_encode($transaction),
            'thash' => $thash,
            'public_key' => $public_key,
            'signature' => $signature
        ];

        return $item;
    }
}
