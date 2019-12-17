<?php

namespace Saseul\Script\Test;

use Saseul\Common\Script;
use Saseul\Core\Env;
use Saseul\Core\Key;
use Saseul\Core\Rule;
use Saseul\Data\Tracker;
use Saseul\Util\DateTime;
use Saseul\Util\RestCall;
use Saseul\Version;

class Farming extends Script
{
    function main()
    {
        $validator = Tracker::getRandomValidator();
        $host = $validator['host'] ?? '';
        $rest = RestCall::GetInstance();

        for ($i = 0; $i < 40000000; $i++) {
            $rs = $rest->post('http://'.$host.'/transaction', $this->item1());
        }
    }

    function item1()
    {
        $private_key = Key::makePrivateKey();
        $public_key = Key::makePublicKey($private_key);
        $address = Key::makeAddress($public_key);

        $type = 'Farming';
        $transaction = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => $address,
            'timestamp' => DateTime::microtime() + Rule::MICROINTERVAL_OF_CHUNK,
            'amount' => (string) rand(1, 999),
        ];

        $thash = Rule::hash($transaction);
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
