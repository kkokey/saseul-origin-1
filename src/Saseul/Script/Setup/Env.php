<?php

namespace Saseul\Script\Setup;

use Saseul\Common\Script;
use Saseul\Constant\Directory;
use Saseul\Constant\Structure;
use Saseul\Core\Key;
use Saseul\Util\Logger;
use Saseul\Util\TypeChecker;

class Env extends Script
{
    private $env;

    function __construct()
    {
        $this->env = Structure::ENV;
    }

    function main()
    {
        $ask = $this->ask('Do you want to set up the env file? [y/n] ');

        if ($ask !== 'y') {
            Logger::log('OK, Stopped. '. PHP_EOL);
            return;
        }

        $ask = $this->ask('Do you want to set SASEUL user/group as the default? - saseul:saseul [y/n] ');

        if ($ask === 'y') {
            $this->env['user'] = 'saseul';
            $this->env['group'] = 'saseul';
        } else {
            $user = $this->ask('Please enter SASEUL user name (ex) saseul) ');
            $group = $this->ask('Please enter SASEUL group name (ex) saseul) ');

            $this->env['user'] = $user;
            $this->env['group'] = $group;
        }

        $ask = $this->ask('Do you want to set Memcached config as the default? - localhost:11211 [y/n] ');

        if ($ask === 'y') {
            $this->env['memcached']['host'] = 'localhost';
            $this->env['memcached']['port'] = 11211;

        } else {
            $memcached_host = $this->ask('Please enter Memcached host (ex) 127.0.0.1)');
            $memcached_port = $this->ask('Please enter Memcached port (ex) 11211)');

            $this->env['memcached']['host'] = $memcached_host;
            $this->env['memcached']['port'] = $memcached_port;
        }

        $ask = $this->ask('Do you want to set MongoDB config as the default? - localhost:27017 [y/n] ');

        if ($ask === 'y') {
            $this->env['mongo_db']['host'] = 'localhost';
            $this->env['mongo_db']['port'] = 27017;

        } else {
            $mongo_db_host = $this->ask('Please enter MongoDB host (ex) 127.0.0.1)');
            $mongo_db_port = $this->ask('Please enter MongoDB port (ex) 27017)');

            $this->env['mongo_db']['host'] = $mongo_db_host;
            $this->env['mongo_db']['port'] = $mongo_db_port;
        }

        $ask = $this->ask('Do you want to make new node account? [y/n] ');

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

        $ask = $this->ask('Are you genesis node? [y/n] ');

        if ($ask === 'y') {
            $this->env['genesis']['address'] = $this->env['node']['address'];
        } else {
            $this->askGenesisAddress();
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

    function askGenesisAddress() {
        $address = $this->ask('Please enter genesis node address. ');

        if (!Key::isValidAddressSize($address)) {
            Logger::log("Invalid address size. ");
            $this->askGenesisAddress();
            return;
        }

        $this->env['genesis']['address'] = $address;
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
        Logger::log('Memcached host: '. $this->env['memcached']['host']);
        Logger::log('Memcached port: '. $this->env['memcached']['port']);
        Logger::log('MongoDB host: '. $this->env['mongo_db']['host']);
        Logger::log('MongoDB port: '. $this->env['mongo_db']['port']);
        Logger::log('Node private key: '. $this->env['node']['private_key']);
        Logger::log('Node public key: '. $this->env['node']['public_key']);
        Logger::log('Node address: '. $this->env['node']['address']);
        Logger::log('Genesis node address: '. $this->env['genesis']['address']);
        Logger::log('');

        file_put_contents(Directory::ENV_FILE, json_encode($this->env));
        Logger::log("OK, It's done. ");
    }
}
