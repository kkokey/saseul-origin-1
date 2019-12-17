<?php

namespace Saseul\Consensus;

use Saseul\Constant\Structure;
use Saseul\Core\Env;
use Saseul\Core\Key;
use Saseul\Core\Property;
use Saseul\Core\Rule;
use Saseul\Data\Chunk;
use Saseul\Data\Tracker;
use Saseul\Util\DateTime;
use Saseul\Util\Parser;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

class ChunkManager
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

    public function collectApiChunk(array $aliveValidators, int $min_time, int $max_time, string $round_key)
    {
        $hosts = [];

        $req_time = DateTime::microtime();
        $data = [
            'min_time' => $min_time,
            'max_time' => $max_time,
            'req_time' => $req_time,
            'public_key' => Env::getPublicKey(),
            'signature' => Key::makeSignature($req_time, Env::getPrivateKey(), Env::getPublicKey()),
        ];

        foreach ($aliveValidators as $node) {
            $hosts[] = $node['host'];
        }

        $results = $this->rest->MultiPOST($hosts, 'broadcast2', $data, false, [], 3);

        $this->collectChunk($results, $max_time, $round_key);
    }

    public function collectBroadcastChunk(array $aliveValidators, int $maxTime, string $round_key)
    {
        $reqTime = DateTime::microtime();

        $hosts = [];

        foreach ($aliveValidators as $node) {
            $hosts[] = $node['host'];
        }

        # try;
        $broadcastCode = Chunk::broadcastCode($round_key);

        $data = [
            'broadcast_code' => $broadcastCode,
            's_timestamp' => $maxTime,
            'req_time' => $reqTime,
            'public_key' => Env::getPublicKey(),
            'signature' => Key::makeSignature($reqTime, Env::getPrivateKey(), Env::getPublicKey()),
        ];

        $results = $this->rest->MultiPOST($hosts, 'broadcast3', $data, false, [], 3);
        $this->collectChunk($results, $maxTime, $round_key);
        $broadcastCodes = $this->collectBroadcastCode($results, []);
        $most = Parser::findMostItem(array_values($broadcastCodes), 'broadcast_code');

        if ($most['item'] === [] || $most['item']['broadcast_code'] === $broadcastCode) {
            return;
        }

        # retry;
        $broadcastCode = Chunk::broadcastCode($round_key);
        $data = [
            'broadcast_code' => $broadcastCode,
            's_timestamp' => $maxTime,
            'req_time' => $reqTime,
            'public_key' => Env::getPublicKey(),
            'signature' => Key::makeSignature($reqTime, Env::getPrivateKey(), Env::getPublicKey()),
        ];

        $results = $this->rest->MultiPOST($hosts, 'broadcast3', $data, false, [], 3);
        $this->collectChunk($results, $maxTime, $round_key);

        return;
    }

    public function collectBroadcastCode($results, $oldCodes)
    {
        $broadcastCodes = $oldCodes;

        foreach ($results as $rs) {
            $result = json_decode($rs['result'], true);

            # check structure;
            if (!isset($result['data'])) {
                continue;
            }

            # check structure;
            if (TypeChecker::StructureCheck(Structure::BROADCAST_RESULT, $result['data']) === false) {
                continue;
            }

            $broadcastCodes[$result['data']['address']] = [
                'address' => $result['data']['address'],
                'broadcast_code' => $result['data']['broadcast_code'],
            ];
        }

        return $broadcastCodes;
    }

    public function collectChunk(array $results, int $max_time, string $round_key)
    {
        $max = 0;
        $validators = Tracker::getValidatorAddress();
        $chunk_info = Property::chunkInfo($round_key);

        foreach ($results as $rs) {
            $result = json_decode($rs['result'], true);

            if ($rs['exec_time'] > $max) {
                $max = $rs['exec_time'];
            }

            # check structure;
            if (!isset($result['data']['items']) || !is_array($result['data']['items'])) {
                continue;
            }

            foreach ($result['data']['items'] as $item) {
                # check structure;
                if (TypeChecker::StructureCheck(Structure::BROADCAST_ITEM, $item) === false) {
                    continue;
                }

                $address = $item['address'];
                $transactions = $item['transactions'];
                $public_key = $item['public_key'];
                $content_signature = $item['content_signature'];
                $chunk_key = $round_key.$max_time.$address;

                # check request is valid;
                if (!Key::isValidAddress($address, $public_key) || !in_array($address, $validators) ||
                    !Chunk::isValidContentSignaure($public_key, $max_time, $content_signature, $transactions))
                {
                    continue;
                }

                $contents = json_encode([
                    'public_key' => $public_key,
                    'content_signature' => $content_signature,
                    'transactions' => $transactions
                ]);

                $len = strlen($contents);
                $old_signature = $chunk_info[$address] ?? '';

                # chunk limit;
                # already exists;
                # content signature minimum rule;
                if ($len > Rule::BROADCAST_LIMIT ||
                    ($old_signature !== '' && $old_signature <= $content_signature)) {
                    continue;
                }

                # add broadcast chunk;
                Chunk::makeBroadcastChunk($chunk_key, $contents);
                $chunk_info[$address] = $content_signature;
            }
        }

        Property::chunkInfo($round_key, $chunk_info);
    }
}
