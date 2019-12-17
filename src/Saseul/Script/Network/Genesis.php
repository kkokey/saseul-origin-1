<?php

namespace Saseul\Script\Network;

use Saseul\Common\Script;
use Saseul\Consensus\HAP;
use Saseul\Constant\Directory;
use Saseul\Constant\Structure;
use Saseul\Core\Debugger;
use Saseul\Core\Env;
use Saseul\Core\Key;
use Saseul\Core\Rule;
use Saseul\Data\Chunk;
use Saseul\Util\DateTime;
use Saseul\Util\Logger;
use Saseul\Util\TypeChecker;
use Saseul\Version;

class Genesis extends Script
{
    private $env;
    private $system;

    function __construct()
    {
        $this->env = Structure::ENV;
        $this->system = HAP::GetInstance();
    }

    function main()
    {
        $env = file_get_contents(Directory::ENV_FILE);
        $env = json_decode($env, true);
        $this->env = $env;

        $key = $this->ask('Please enter genesis key. (or message) ');
        $this->env['genesis']['address'] = $this->env['node']['address'];

        $this->writeEnv();

        $timestamp = DateTime::microtime();
        $expect_standard_timestamp = Chunk::getTID($timestamp + Rule::MICROINTERVAL_OF_CHUNK);

        $item = $this->genesisItem($timestamp, $key);
        $thash = $item['thash'];

        $transactions = [];
        $transactions[] = $item;

        $this->system->forceCommit($transactions, $expect_standard_timestamp);
        $request_item = $this->requestItem($thash);
        $result = $request_item['result'] ?? 'reject';

        Debugger::info($result);
        Logger::log('result: '.$result);
    }

    function genesisItem($timestamp, $key)
    {
        $transaction = [
            'type' => 'Genesis',
            'version' => Version::CURRENT,
            'from' => Env::getAddress(),
            'key' => $key,
            'timestamp' => $timestamp
        ];

        $thash = Rule::hash($transaction);
        $private_key = Env::getPrivateKey();
        $public_key = Env::getPublicKey();
        $signature = Key::makeSignature($thash, $private_key, $public_key);

        $item = [
            'transaction' => $transaction,
            'thash' => $thash,
            'public_key' => $public_key,
            'signature' => $signature
        ];

        return $item;
    }

    function requestItem($thash)
    {
        $type = 'GetTransaction';
        $request = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => Env::getAddress(),
            'thash' => $thash,
            'timestamp' => DateTime::microtime()
        ];

        $rhash = Rule::hash($request);
        $private_key = Env::getPrivateKey();
        $public_key = Env::getPublicKey();
        $signature = Key::makeSignature($rhash, $private_key, $public_key);

        return $this->system->localRequest($type, $request, $rhash, $public_key, $signature);
    }

    function writeEnv()
    {
        if (!TypeChecker::structureCheck(Structure::ENV, $this->env))
        {
            Logger::log('');
            Logger::log('Env parameter error. Please check input. '. PHP_EOL);
            return;
        }

        Logger::log('');
        Logger::log('Genesis node address: '. $this->env['genesis']['address']);
        Logger::log('');

        file_put_contents(Directory::ENV_FILE, json_encode($this->env));
    }
}
