<?php

namespace Saseul\Consensus;

use Saseul\Core\Env;
use Saseul\Constant\Structure;
use Saseul\Core\Property;
use Saseul\Core\Key;
use Saseul\Core\Rule;
use Saseul\Data\Tracker;
use Saseul\Util\DateTime;
use Saseul\Util\Logger;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

class HashManager
{
    private static $instance = null;

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private $rest;

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
    }

    public function initHashInfo($expect_s_timestamp, $round_number, $last_blockhash, $round_key)
    {
        $myAddress = Env::getAddress();
        $now = DateTime::microtime();

        $decision = [
            'round_number' => $round_number,
            'last_blockhash' => $last_blockhash,
            'blockhash' => '',
            's_timestamp' => $expect_s_timestamp,
            'transaction_count' => 0,
            'timestamp' => $now,
            'round_key' => $round_key,
            'chunk_info' => Property::chunkInfo($round_key),
        ];

        $public_key = Env::getPublicKey();
        $hash = Rule::hash($decision);
        $signature = Key::makeSignature($hash, Env::getPrivateKey(), Env::getPublicKey());

        $myHashInfo = [
            'decision' => $decision,
            'public_key' => $public_key,
            'hash' => $hash,
            'signature' => $signature,
        ];

        # save
        Property::hashInfo($round_key, [$myAddress => $myHashInfo]);

        return $myHashInfo;
    }

    public function myHashInfo($expect_block, $round_number, $last_blockhash, $round_key)
    {
        $myAddress = Env::getAddress();
        $now = DateTime::microtime();

        $decision = [
            'round_number' => $round_number,
            'last_blockhash' => $last_blockhash,
            'blockhash' => $expect_block['blockhash'],
            's_timestamp' => $expect_block['s_timestamp'],
            'transaction_count' => $expect_block['transaction_count'],
            'timestamp' => $now,
            'round_key' => $round_key,
            'chunk_info' => Property::chunkInfo($round_key),
        ];

        $public_key = Env::getPublicKey();
        $hash = Rule::hash($decision);
        $signature = Key::makeSignature($hash, Env::getPrivateKey(), Env::getPublicKey());

        $myHashInfo = [
            'decision' => $decision,
            'public_key' => $public_key,
            'hash' => $hash,
            'signature' => $signature,
        ];

        # save
        Property::hashInfo($round_key, [$myAddress => $myHashInfo]);

        return $myHashInfo;
    }

    public function netHashInfo($roundKey, $aliveValidators)
    {
        $hashInfos = [];
        $hosts = [];

        foreach ($aliveValidators as $validator) {
            $hosts[] = $validator['host'];
        }

        # 3 times;
        for ($i = 0; $i < 3; $i++) {
            $now = DateTime::microtime();

            $results = $this->rest->MultiPOST($hosts, 'hashinfo', ['round_key' => $roundKey]);

            foreach ($results as $item) {
                $r = json_decode($item['result'], true);

                if (!isset($r['data']) || !is_array($r['data'])) {
                    continue;
                }

                foreach ($r['data'] as $address => $blockhash) {
                    if (isset($hashInfos[$address])) {
                        continue;
                    }

                    if (TypeChecker::StructureCheck(Structure::HASH_INFO, $blockhash) === false) {
                        continue;
                    }

                    if ($this->checkHashRequest($address, $blockhash) === false) {
                        continue;
                    }

                    $hashInfos[$address] = $blockhash;
                }
            }

            if (count($hashInfos) === count($aliveValidators)) {
                return $hashInfos;
            }

            $wait = DateTime::microtime() - $now;

            if ($wait < 200000) {
                usleep(200000 - $wait);
            }
        }

        return $hashInfos;
    }

    public function bestHashInfo($my_hash_info, $net_hash_info)
    {
        # init;
        $round_key = $my_hash_info['decision']['round_key'];
        $data_different = false;

        # best;
        $best_s_timestamp = $my_hash_info['decision']['s_timestamp'];
        $best_chunk_info = $my_hash_info['decision']['chunk_info'];
        $best_hash_info = [
            'address' => Env::getAddress(),
            'blockhash' => $my_hash_info['decision']['blockhash'],
        ];

        if (count($net_hash_info) === 0) {
            return $best_hash_info;
        }

        # first;
        foreach ($net_hash_info as $address => $item)
        {
            $decision = $item['decision'];

            $item_round_key = $decision['round_key'];
            $s_timestamp = $decision['s_timestamp'];
            $blockhash = $decision['blockhash'];
            $chunk_info = $decision['chunk_info'];

            # no count;
            if ($round_key !== $item_round_key) {
                continue;
            }

            # minimum s timestamp
            if ($s_timestamp < $best_s_timestamp) {
                $best_s_timestamp = $s_timestamp;
                $best_chunk_info = $chunk_info;
                $best_hash_info['address'] = $address;
                $best_hash_info['blockhash'] = $blockhash;
                continue;
            }

            # largest;
            if ($s_timestamp === $best_s_timestamp
                && count($chunk_info) > count($best_chunk_info))
            {
                $best_s_timestamp = $s_timestamp;
                $best_chunk_info = $chunk_info;
                $best_hash_info['address'] = $address;
                $best_hash_info['blockhash'] = $blockhash;
            }
        }

        # check;
        foreach ($net_hash_info as $address => $item)
        {
            $decision = $item['decision'];

            $item_round_key = $decision['round_key'];
            $s_timestamp = $decision['s_timestamp'];
            $chunk_info = $decision['chunk_info'];

            # no count;
            if ($round_key !== $item_round_key && $s_timestamp > $best_s_timestamp) {
                continue;
            }

            $diff = array_diff($chunk_info, $best_chunk_info);

            if (count($diff) > 0)
            {
                $data_different = true;
                break;
            }
        }

        if ($data_different === true)
        {
            $best_hash_info['address'] = '';
            $best_hash_info['blockhash'] = '';
        }

        return $best_hash_info;
    }

    public function checkHashRequest($address, $value): bool
    {
        $round_number = $value['decision']['round_number'];
        $last_blockhash = $value['decision']['last_blockhash'];
        $round_key = $value['decision']['round_key'];
        $chunk_info = $value['decision']['chunk_info'];
        $public_key = $value['public_key'];
        $signature = $value['signature'];
        $hash = Rule::hash($value['decision']);

        return Key::isValidSignature($hash, $public_key, $signature)
            && (Key::makeAddress($public_key) === $address)
            && (Rule::roundKey($last_blockhash, $round_number) === $round_key)
            && $this->isValidChunkInfo($chunk_info);
    }

    public function isValidChunkInfo($chunk_info): bool
    {
        if (!is_array($chunk_info)) {
            return false;
        }

        foreach ($chunk_info as $address => $sig)
        {
            if (!Key::isValidAddressSize($address) || !is_string($sig)) {
                return false;
            }
        }

        $validators = Tracker::getValidatorAddress();
        $addresses = array_keys($chunk_info);
        $diff = array_diff($addresses, $validators);

        if (count($diff) > 0) {
            return false;
        }

        return true;
    }
}
