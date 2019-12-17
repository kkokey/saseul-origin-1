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

class Transaction extends Script
{
    function main()
    {
        $msg = $this->ask('message? ');

        $item = $this->item($msg);
        $validator = Tracker::getRandomValidator();
        $host = $validator['host'] ?? '';

        if ($host === '') {
            Logger::log($validator);
            exit();
        }

        $rest = RestCall::GetInstance();

        for ($i = 0; $i < 40000000; $i++) {
//        for ($i = 0; $i < 1; $i++) {
            $rs = $rest->post('http://'.$host.'/transaction', $this->item($msg));
        }
    }

    function item($msg)
    {
        $type = 'Dummy';
        $transaction = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => Env::getAddress(),
            'timestamp' => DateTime::microtime() + Rule::MICROINTERVAL_OF_CHUNK,
            'message' => $msg,
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
