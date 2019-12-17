<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Env;
use Saseul\Core\Key;
use Saseul\Core\Rule;
use Saseul\Data\Chunk;
use Saseul\Data\Tracker;
use Saseul\Util\DateTime;

class Broadcast2 extends Api
{
    public function main()
    {
        $min_time = (int) $this->getParam($_REQUEST, 'min_time', ['default' => 0]);
        $max_time = (int) $this->getParam($_REQUEST, 'max_time', ['default' => DateTime::Microtime()]);
        $req_time = (int) $this->getParam($_REQUEST, 'req_time');
        $public_key = $this->getParam($_REQUEST, 'public_key');
        $signature = $this->getParam($_REQUEST, 'signature');

        if ($max_time > DateTime::Microtime()) {
            $max_time = DateTime::Microtime();
        }

        $this->checkParam($req_time, $public_key, $signature);

        $address = Env::getAddress();
        $txs = Chunk::txFromApiChunk($min_time, $max_time);
        $cacheKey = "chunksig_{$address}_{$max_time}";
        $publicKey = Env::getPublicKey();

        $contentSignature = Chunk::contentSignature($cacheKey, $max_time, $txs);

        $item = [
            'address' => $address,
            'transactions' => $txs,
            'public_key' => $publicKey,
            'content_signature' => $contentSignature,
        ];

        $this->data = [
            'items' => [$item]
        ];
    }

    public function checkParam($req_time, $public_key, $signature)
    {
        if (!is_string($public_key) || !is_string($signature)) {
            $this->error('Invalid public key & signature.');
        }

        if (!Tracker::isValidator(Key::makeAddress($public_key))) {
            $this->error('You are not validator. ');
        }

        if ((abs(DateTime::Microtime() - $req_time) > Rule::REQUEST_VALIDTIME) ||
            !Key::isValidSignature($req_time, $public_key, $signature)) {
            $this->error('Invalid signature. ');
        }
    }
}
