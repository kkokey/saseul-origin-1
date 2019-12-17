<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Env;
use Saseul\Core\Key;
use Saseul\Core\Rule;
use Saseul\Data\Chunk;
use Saseul\Data\Tracker;
use Saseul\Util\DateTime;

class Broadcast3 extends Api
{
    private $broadcast_code;
    private $s_timestamp;
    private $round_key;
    private $req_time;
    private $public_key;
    private $signature;

    public function main()
    {
        $this->broadcast_code = $this->getParam($_REQUEST, 'broadcast_code', ['default' => '']);
        $this->s_timestamp = (int)$this->getParam($_REQUEST, 's_timestamp', ['default' => 0]);
        $this->round_key = $this->getParam($_REQUEST, 'round_key');
        $this->req_time = (int) $this->getParam($_REQUEST, 'req_time');
        $this->public_key = $this->getParam($_REQUEST, 'public_key');
        $this->signature = $this->getParam($_REQUEST, 'signature');

        $this->checkParam();

        $myBroadcastCode = Chunk::broadcastCode($this->round_key);
        $items = $this->pickItems($myBroadcastCode);

        $this->data = [
            'items' => $items,
            'broadcast_code' => $myBroadcastCode,
            'address' => Env::getAddress(),
        ];
    }

    public function pickItems($myBroadcastCode)
    {
        $items = [];
        $needles = [];
        $validators = Tracker::getValidatorAddress();
        sort($validators);

        if (count($validators) !== mb_strlen($this->broadcast_code)) {
            return $items;
        }

        for ($i = 0; $i < mb_strlen($this->broadcast_code); $i++) {
            if ($this->broadcast_code[$i] === '0' && $myBroadcastCode[$i] === '1') {
                $needles[] = $validators[$i];
            }
        }

        shuffle($needles);

        if (count($needles) === 0) {
            return $items;
        }

        $address = array_pop($needles);

        $chunk_key = $this->round_key.$this->s_timestamp.$address;
        $broadcastChunk = Chunk::broadcastChunk($chunk_key);

        $item = [
            'address' => $address,
            'transactions' => $broadcastChunk['transactions'],
            'public_key' => $broadcastChunk['public_key'],
            'content_signature' => $broadcastChunk['content_signature'],
        ];

        $items[] = $item;

        return $items;
    }

    public function checkParam()
    {
        if (!is_string($this->public_key) || !is_string($this->signature)) {
            $this->error('Invalid public key & signature.');
        }

        if (!Tracker::isValidator(Key::makeAddress($this->public_key))) {
            $this->error('You are not validator. ');
        }

        if ((abs(DateTime::Microtime() - $this->req_time) > Rule::REQUEST_VALIDTIME) ||
            !Key::isValidSignature($this->req_time, $this->public_key, $this->signature)) {
            $this->error('Invalid signature. ');
        }
    }
}
