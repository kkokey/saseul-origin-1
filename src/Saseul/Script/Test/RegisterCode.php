<?php

namespace Saseul\Script\Test;

use Saseul\Common\Script;
use Saseul\Constant\Directory;
use Saseul\Constant\Form;
use Saseul\Core\Env;
use Saseul\Core\Key;
use Saseul\Core\Rule;
use Saseul\Data\Tracker;
use Saseul\Util\DateTime;
use Saseul\Util\RestCall;
use Saseul\Version;

class RegisterCode extends Script
{
    function main()
    {
        $validator = Tracker::getRandomValidator();
        $host = $validator['host'] ?? '';
        $rest = RestCall::GetInstance();

        $items = [];
        $items[] = $this->item1();
        $items[] = $this->item2();
        $items[] = $this->item3();
        $items[] = $this->item4();

        foreach ($items as $item) {
            $rs = $rest->post('http://'.$host.'/transaction', $item);
        }
    }

    function item1()
    {
        $cid = 'S000000000001';
        $filename = Directory::SCRIPT.DIRECTORY_SEPARATOR.'Code'.DIRECTORY_SEPARATOR.$cid.'.php';
        $code = file_get_contents($filename);

        $type = 'RegisterCode';
        $transaction = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => Env::getAddress(),
            'timestamp' => DateTime::microtime() + Rule::MICROINTERVAL_OF_CHUNK,
            'code' => $code,
            'form' => Form::STATUS,
            'cid' => $cid,
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

    function item2()
    {
        $cid = 'C000000000001';
        $filename = Directory::SCRIPT.DIRECTORY_SEPARATOR.'Code'.DIRECTORY_SEPARATOR.$cid.'.php';
        $code = file_get_contents($filename);

        $type = 'RegisterCode';
        $transaction = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => Env::getAddress(),
            'timestamp' => DateTime::microtime() + Rule::MICROINTERVAL_OF_CHUNK,
            'code' => $code,
            'form' => Form::CONTRACT,
            'cid' => $cid,
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

    function item3()
    {
        $cid = 'C000000000002';
        $filename = Directory::SCRIPT.DIRECTORY_SEPARATOR.'Code'.DIRECTORY_SEPARATOR.$cid.'.php';
        $code = file_get_contents($filename);

        $type = 'RegisterCode';
        $transaction = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => Env::getAddress(),
            'timestamp' => DateTime::microtime() + Rule::MICROINTERVAL_OF_CHUNK,
            'code' => $code,
            'form' => Form::CONTRACT,
            'cid' => $cid,
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

    function item4()
    {
        $cid = 'C000000000003';
        $filename = Directory::SCRIPT.DIRECTORY_SEPARATOR.'Code'.DIRECTORY_SEPARATOR.$cid.'.php';
        $code = file_get_contents($filename);

        $type = 'RegisterCode';
        $transaction = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => Env::getAddress(),
            'timestamp' => DateTime::microtime() + Rule::MICROINTERVAL_OF_CHUNK,
            'code' => $code,
            'form' => Form::CONTRACT,
            'cid' => $cid,
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
