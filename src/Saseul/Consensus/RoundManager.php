<?php

namespace Saseul\Consensus;

use Saseul\Core\Env;
use Saseul\Constant\Structure;
use Saseul\Core\IMLog;
use Saseul\Data\Chunk;
use Saseul\Core\Property;
use Saseul\Core\Rule;
use Saseul\Data\Tracker;
use Saseul\Core\Key;
use Saseul\Util\DateTime;
use Saseul\Util\Logger;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

class RoundManager
{
    protected static $instance = null;
    protected $rest;

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function myRound(array $lastBlock): array
    {
        $private_key = Env::getPrivateKey();
        $public_key = Env::getPublicKey();
        $address = Env::getAddress();

        $old_round = Property::round();
        $old_my_round = $old_round[$address] ?? Structure::ROUND;
        $old_decision = $old_my_round['decision'] ?? Structure::ROUND['decision'];
        $old_round_key = $old_decision['round_key'];
        $old_expect_s_timestamp = (int) $old_decision['expect_s_timestamp'];

        $round_number = $lastBlock['block_number'] + 1;
        $last_blockhash = $lastBlock['blockhash'];
        $last_s_timestamp = $lastBlock['s_timestamp'];
        $timestamp = DateTime::microtime();
        $round_key = Rule::roundKey($last_blockhash, $round_number);
        $expect_s_timestamp = Chunk::getExpectStandardTimestamp($last_s_timestamp);

        if ($old_round_key === $round_key && $old_expect_s_timestamp !== 0
        && TypeChecker::StructureCheck(Structure::ROUND, $old_my_round) === true)
        {
            return $old_my_round;
        }

        $decision = [
            'round_number' => $round_number,
            'last_blockhash' => $last_blockhash,
            'last_s_timestamp' => $last_s_timestamp,
            'timestamp' => $timestamp,
            'round_key' => $round_key,
            'expect_s_timestamp' => $expect_s_timestamp,
        ];

        $hash = Rule::hash($decision);
        $signature = Key::makeSignature($hash, $private_key, $public_key);

        $round = [
            'decision' => $decision,
            'public_key' => $public_key,
            'hash' => $hash,
            'signature' => $signature,
        ];

        # save
        Property::round([$address => $round]);

        return $round;
    }

    public function netRound(array $nodes, array $last_block) {
        $last_blockhash = $last_block['blockhash'];
        $round_number = (int)$last_block['block_number'] + 1;
        $round_key = Rule::roundKey($last_blockhash, $round_number);
        $rounds = [];
        $hosts = [];

        foreach ($nodes as $node) {
            $hosts[] = $node['host'];
        }

        $data = ['round_number' => $round_number, 'register' => 1];
        $results = $this->rest->MultiPOST($hosts, 'round', $data);

        foreach ($results as $item) {
            $r = json_decode($item['result'], true);
            $host = $item['host'] ?? '';
            $data = $r['data']['round'] ?? [];
            $key = $r['data']['key'] ?? '';

            if ($key !== '' && $key !== $round_key) {
                # not my network;
                Tracker::excludeRequest($host, true);
                continue;
            }

            foreach ($data as $address => $round) {
                # check exists;
                if (isset($rounds[$address])) {
                    continue;
                }

                # check structure;
                if (TypeChecker::StructureCheck(Structure::ROUND, $round) === false) {
                    continue;
                }

                # check request is valid;
                if ($this->checkRoundRequest($address, $round) === false) {
                    continue;
                }

                # add
                $rounds[$address] = $round;
            }
        }

        return $rounds;
    }

    public function checkRoundRequest($address, $value): bool
    {
        $round_number = $value['decision']['round_number'];
        $last_blockhash = $value['decision']['last_blockhash'];
        $round_key = $value['decision']['round_key'];
        $public_key = $value['public_key'];
        $signature = $value['signature'];
        $hash = Rule::hash($value['decision']);

        return Key::isValidSignature($hash, $public_key, $signature)
            && (Key::makeAddress($public_key) === $address)
            && (Rule::roundKey($last_blockhash, $round_number) === $round_key);
    }

    public function roundInfo(array $my_round, array $net_round, array $last_block): array
    {
        $validatorAddress = Tracker::getValidatorAddress();

        $my_round_number = $my_round['decision']['round_number'];
        $net_round_number = $my_round_number;
        $net_s_timestamp = 0;
        $net_round_leader = Env::getAddress();
        $round_key = $my_round['decision']['round_key'];
        $last_blockhash = $last_block['blockhash'];
        $last_s_timestamp = $last_block['s_timestamp'];

        foreach ($net_round as $round) {
            $decision = $round['decision'];
            $round_number = $decision['round_number'];

            if ($round_number > $net_round_number) {
                $net_round_number = $round_number;
            }
        }

        foreach ($net_round as $address => $round) {
            $decision = $round['decision'];
            $round_number = $decision['round_number'];
            $expect_s_timestamp = $decision['expect_s_timestamp'];

            if ($round_number === $net_round_number && in_array($address, $validatorAddress)) {

                if ($net_s_timestamp === 0) {
                    $net_s_timestamp = $expect_s_timestamp;
                    $net_round_leader = $address;
                    continue;
                }


                if ($expect_s_timestamp < $net_s_timestamp
                    && $expect_s_timestamp > $last_s_timestamp
                    && $expect_s_timestamp % Rule::MICROINTERVAL_OF_CHUNK === 0)
                {
                    $net_s_timestamp = $expect_s_timestamp;
                    $net_round_leader = $address;
                }
            }
        }

        $roundInfo = [
            'my_round_number' => $my_round_number,
            'net_round_number' => $net_round_number,
            'net_s_timestamp' => $net_s_timestamp,
            'net_round_leader' => $net_round_leader,
            'round_key' => $round_key,
            'last_blockhash' => $last_blockhash,
            'last_s_timestamp' => $last_s_timestamp,
        ];

        # save
        Property::roundInfo($roundInfo);

        return $roundInfo;
    }
}
