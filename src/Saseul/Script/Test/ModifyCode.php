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

class ModifyCode extends Script
{
    function main()
    {
        $validator = Tracker::getRandomValidator();
        $host = $validator['host'] ?? '';
        $rest = RestCall::GetInstance();

        $items = [];
        $items[] = $this->item2();

        foreach ($items as $item) {
            $rs = $rest->post('http://'.$host.'/transaction', $item);
        }
    }

    function item2()
    {
        $cid = 'C000000000001';
        $filename = Directory::SCRIPT.DIRECTORY_SEPARATOR.'Code'.DIRECTORY_SEPARATOR.$cid.'_modify.php';
        $code = file_get_contents($filename);

        $type = 'ModifyCode';
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
