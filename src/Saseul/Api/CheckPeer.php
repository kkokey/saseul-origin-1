<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Env;
use Saseul\Core\Key;

class CheckPeer extends Api
{
    function main()
    {
        $checksum = $this->getParam($_REQUEST, 'checksum');

        if (!is_string($checksum) || mb_strlen($checksum) !== 32) {
            $this->error('Invalid checksum: '.$checksum);
        }

        $your_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $private_key = Env::getPrivateKey();
        $public_key = Env::getPublicKey();
        $address = Env::getAddress();
        $signature = Key::makeSignature($checksum, $private_key, $public_key);

        $this->data = [
            'your_ip' => $your_ip,
            'string' => $checksum,
            'public_key' => $public_key,
            'address' => $address,
            'signature' => $signature,
        ];
    }
}