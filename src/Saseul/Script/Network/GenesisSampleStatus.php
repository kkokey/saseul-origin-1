<?php

namespace Saseul\Script\Network;

use Saseul\Common\Script;
use Saseul\Core\Env;
use Saseul\Core\Key;
use Saseul\Core\Rule;
use Saseul\Data\Tracker;
use Saseul\Util\DateTime;
use Saseul\Util\Logger;
use Saseul\Util\RestCall;
use Saseul\Version;

class GenesisSampleStatus extends Script
{
    function __construct()
    {
        Env::registerErrorHandler();
    }

    function main()
    {
        $items = [];
        $items[] = $this->genesisRoleToken();
        $items[] = $this->genesisCoin();

        $validator = Tracker::getRandomValidator();
        $host = $validator['host'] ?? '';
        $rest = RestCall::GetInstance();

        if ($host === '') {
            Logger::log('There is no validators ');
        }

        foreach ($items as $item) {
            $rs = $rest->post('http://'.$host.'/transaction', $item);
            Logger::log($rs);
        }

        Logger::log('OK. ');
    }

    function genesisRoleToken()
    {
        $type = 'GenesisRoleToken';
        $transaction = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => Env::getAddress(),
            'timestamp' => DateTime::microtime() + Rule::MICROINTERVAL_OF_CHUNK,
            'validator_amount' => '200',
            'network_manager_amount' => '5',
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

    function genesisCoin()
    {
        $type = 'GenesisCoin';
        $transaction = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => Env::getAddress(),
            'timestamp' => DateTime::microtime() + Rule::MICROINTERVAL_OF_CHUNK,
            'amount' => '1000000000000000000000000',
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
