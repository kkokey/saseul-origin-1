<?php

namespace Saseul\Script\Account;

use Saseul\Common\Script;
use Saseul\Constant\MongoDbConfig;
use Saseul\Core\Env;
use Saseul\System\Database;
use Saseul\Util\Logger;

class Info extends Script
{
    private $db;

    function __construct()
    {
        $this->db = Database::GetInstance();
    }

    function main()
    {
        Logger::log('');
        Logger::log('[Account Info]');
        Logger::log(' - Private key: '. Env::getPrivateKey());
        Logger::log(' - Public key: '. Env::getPublicKey());
        Logger::log(' - Address: '. Env::getAddress());
        Logger::log('');
        Logger::log('[Role Token Info]');

        $roleTokenInfo = $this->getRoleToken();

        if ($roleTokenInfo !== []) {

            foreach ($roleTokenInfo as $info)
            {
                Logger::log(' - '.$info['token_name'].': '.$info['balance']);
            }
        } else {
            Logger::log(' You have no role token. ');
        }

        Logger::log('');
        Logger::log('[Coin Info]');

        $coinInfo = $this->getCoin();

        if ($coinInfo !== []) {
            Logger::log(' - Balance: '.$coinInfo['balance']);
            Logger::log(' - Deposit: '.$coinInfo['deposit']);
        } else {
            Logger::log(' You have no balance. ');
        }

        Logger::log('');
    }

    function getRoleToken()
    {
        $namespace = MongoDbConfig::DB_CUSTOM.'.role_token';
        $filter = ['address' => Env::getAddress()];
        $rs = $this->db->Query($namespace, $filter);
        $infos = [];

        foreach ($rs as $item)
        {
            $info = [];
            $info['token_name'] = $item->token_name ?? '';
            $info['balance'] = $item->balance ?? '';

            $infos[] = $info;
        }

        return $infos;
    }

    function getCoin()
    {
        $namespace = MongoDbConfig::DB_CUSTOM.'.coin';
        $filter = ['address' => Env::getAddress()];
        $rs = $this->db->Query($namespace, $filter);
        $info = [];

        foreach ($rs as $item)
        {
            $info['balance'] = $item->balance ?? '';
            $info['deposit'] = $item->deposit ?? '';
        }

        return $info;
    }
}
