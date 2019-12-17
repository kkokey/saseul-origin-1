<?php

namespace Saseul\Script\Setup;

use Saseul\Common\Script;
use Saseul\Constant\Directory;
use Saseul\Constant\Structure;
use Saseul\Core\Key;
use Saseul\Util\Logger;
use Saseul\Util\TypeChecker;

class Account extends Script
{
    private $env;

    function __construct()
    {
        $this->env = Structure::ENV;
    }

    function main()
    {
        $ask = $this->ask('Do you want to make new node account? [y/n] ');

        $env = file_get_contents(Directory::ENV_FILE);
        $env = json_decode($env, true);

        $this->env = $env;

        if ($ask === 'y')
        {
            $private_key = Key::makePrivateKey();
            $public_key = Key::makePublicKey($private_key);
            $address = Key::makeAddress($public_key);

            $this->env['node']['private_key'] = $private_key;
            $this->env['node']['public_key'] = $public_key;
            $this->env['node']['address'] = $address;

        } else {
            $this->askPrivateKey();
        }

        $this->writeEnv();
    }

    function askPrivateKey() {
        $private_key = $this->ask('Please enter your private key. ');

        if (!Key::isValidKeySize($private_key)) {
            Logger::log("Invalid key size. ");
            $this->askPrivateKey();
            return;
        }

        $public_key = Key::makePublicKey($private_key);
        $address = Key::makeAddress($public_key);

        $this->env['node']['private_key'] = $private_key;
        $this->env['node']['public_key'] = $public_key;
        $this->env['node']['address'] = $address;
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
        Logger::log('Node private key: '. $this->env['node']['private_key']);
        Logger::log('Node public key: '. $this->env['node']['public_key']);
        Logger::log('Node address: '. $this->env['node']['address']);
        Logger::log('');

        file_put_contents(Directory::ENV_FILE, json_encode($this->env));
        Logger::log("OK, It's done. ");
    }
}
