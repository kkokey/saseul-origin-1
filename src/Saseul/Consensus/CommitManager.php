<?php

namespace Saseul\Consensus;

use Saseul\Committer\ContractManager;
use Saseul\Committer\StatusManager;
use Saseul\Constant\Decision;
use Saseul\Constant\Directory;
use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\Structure;
use Saseul\Core\Key;
use Saseul\Core\Property;
use Saseul\Data\Chunk;
use Saseul\Data\Tracker;
use Saseul\System\Database;
use Saseul\Core\Rule;
use Saseul\Util\DateTime;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

class CommitManager
{
    private static $instance = null;

    private $db;
    private $rest;
    private $contract_manager;
    private $status_manager;

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->rest = RestCall::GetInstance();
        $this->contract_manager = new ContractManager();
        $this->status_manager = new StatusManager();
    }

    public function init()
    {
        $this->status_manager->reset();
    }

    public function mergedTransactions($min_time, $max_time, $round_key) {
        $len = 0;
        $keys = [];
        $txs = [];
        $addresses = [];
        $signatures = [];

        $chunk_info = Property::chunkInfo($round_key);

        foreach ($chunk_info as $address => $item)
        {
            $addresses[] = $address;
            $signatures[] = $item;
        }

        array_multisort($signatures, $addresses);

        foreach ($addresses as $address)
        {
            $chunk_key = $round_key.$max_time.$address;
            $broadcast_chunk = Chunk::broadcastChunk($chunk_key);
            $transactions = $broadcast_chunk['transactions'];

            foreach ($transactions as $item) {
                if (TypeChecker::StructureCheck(Structure::TX_ITEM, $item) === false) {
                    continue;
                }

                $transaction = $item['transaction'];
                $thash = Rule::hash($transaction);
                $public_key = $item['public_key'];
                $signature = $item['signature'];

                $type = $transaction['type'];
                $timestamp = $transaction['timestamp'];
                $order_key = $timestamp . $thash;

                if ($timestamp > $max_time || $min_time >= $timestamp || in_array($order_key, $keys)) {
                    continue;
                }

                $this->contract_manager->initTransaction($type, $transaction, $thash, $public_key, $signature);

                if ($this->contract_manager->getTransactionValidity() === false) {
                    continue;
                }

                $this->contract_manager->loadStatus();

                $tx = [
                    'thash' => $thash,
                    'timestamp' => $timestamp,
                    'transaction' => $transaction,
                    'public_key' => $public_key,
                    'signature' => $signature,
                    'result' => '',
                ];

                $txs[] = $tx;
                $keys[] = $order_key;
                $len+= strlen(json_encode($tx));

                if ($len > Rule::BLOCK_LIMIT) {
                    break;
                }
            }

            if ($len > Rule::BLOCK_LIMIT) {
                break;
            }
        }

        array_multisort($keys, $txs);

        return $txs;
    }

    public function nextBlock(
        int $round_number, string $last_blockhash, string $blockhash,
        int $txCount, int $standardTimestamp, string $public_key, string $signature)
    {
        return [
            'block_number' => $round_number,
            'last_blockhash' => $last_blockhash,
            'blockhash' => $blockhash,
            'transaction_count' => $txCount,
            's_timestamp' => $standardTimestamp,
            'timestamp' => DateTime::microtime(),
            'public_key' => $public_key,
            'signature' => $signature,
        ];
    }

    public function commit($transactions, $last_block, $expect_block, $force = false): bool
    {
        if (count($transactions) === 0) {
            return false;
        }

        # genesis exclude;
        if ($expect_block['block_number'] > 1) {
            if (!Key::isValidSignature($expect_block['blockhash'], $expect_block['public_key'], $expect_block['signature'])) {
                return false;
            }

            if ($force === false && !Tracker::isValidator(Key::makeAddress($expect_block['public_key']))) {
                return false;
            }
        }

        $this->status_manager->preprocess();
        $this->status_manager->save();
        $this->status_manager->postprocess();

        $this->commitTransaction($transactions, $expect_block);
        $this->commitBlock($expect_block);

        Chunk::removeAPIChunk($last_block['s_timestamp']);
        Chunk::removeBroadcastChunk(
            Rule::roundKey($last_block['last_blockhash'], $last_block['block_number'])
        );

        return true;
    }

    public function orderedTransactions($transactions, $minTimestamp, $maxTimestamp) {
        $orderKey = [];
        $txs = [];

        $this->status_manager->reset();
        
        foreach ($transactions as $key => $item) {
            if (TypeChecker::StructureCheck(Structure::TX_ITEM, $item) === false) {
                continue;
            }

            $transaction = $item['transaction'];
            $thash = Rule::hash($transaction);
            $public_key = $item['public_key'];
            $signature = $item['signature'];

            $type = $transaction['type'];
            $timestamp = $transaction['timestamp'];

            if ($timestamp > $maxTimestamp || $timestamp < $minTimestamp) {
                continue;
            }

            $this->contract_manager->initTransaction($type, $transaction, $thash, $public_key, $signature);
            $validity = $this->contract_manager->getTransactionValidity();
            $this->contract_manager->loadStatus();

            if ($validity == false) {
                continue;
            }

            $txs[] = [
                'thash' => $thash,
                'timestamp' => $timestamp,
                'transaction' => $transaction,
                'public_key' => $public_key,
                'signature' => $signature,
                'result' => '',
            ];

            $orderKey[] = $timestamp . $thash;
        }

        array_multisort($orderKey, $txs);

        return $txs;
    }

    public function completeTransactions($transactions)
    {
        # load status;
        $this->status_manager->load();

        foreach ($transactions as $key => $item) {
            $transaction = $item['transaction'];
            $thash = $item['thash'];
            $public_key = $item['public_key'];
            $signature = $item['signature'];

            $type = $transaction['type'];

            $this->contract_manager->initTransaction($type, $transaction, $thash, $public_key, $signature);
            $this->contract_manager->getStatus();
            $result = $this->contract_manager->makeDecision();
            $transactions[$key]['result'] = $result;

            if ($result === Decision::REJECT) {
                continue;
            }

            $this->contract_manager->setStatus();
        }

        return $transactions;
    }

    public function commitTransaction($transactions, $expectBlock)
    {
        $blockhash = $expectBlock['blockhash'];

        foreach ($transactions as $transaction) {
            $transaction['block'] = $blockhash;
            $filter = ['thash' => $transaction['thash'], 'timestamp' => $transaction['timestamp']];
            $row = ['$set' => $transaction];
            $opt = ['upsert' => true];
            $this->db->bulk->update($filter, $row, $opt);
        }

        if ($this->db->bulk->count() > 0) {
            $this->db->BulkWrite(MongoDbConfig::NAMESPACE_TRANSACTION);
        }
    }

    public function commitBlock($expectBlock)
    {
        $this->db->bulk->insert($expectBlock);

        if ($this->db->bulk->count() > 0) {
            $this->db->BulkWrite(MongoDbConfig::NAMESPACE_BLOCK);
        }
    }

    public function makeTransactionChunk($expectBlock, $transactions)
    {
        $block_number = $expectBlock['block_number'];
        $chunkname = $expectBlock['blockhash'] . $expectBlock['s_timestamp'] . '.json';

        $transaction_dir = Chunk::txSubDir($block_number);
        $make = Chunk::makeTxSubDir($block_number);

        if ($make === true) {
            Chunk::makeTxArchive((int) $block_number - 1);
        }

        $transaction_chunk = Directory::TRANSACTIONS.DIRECTORY_SEPARATOR.$transaction_dir.DIRECTORY_SEPARATOR.$chunkname;

        if (file_exists($transaction_chunk)) {
            return;
        }

        $file = fopen($transaction_chunk, 'a');

        foreach ($transactions as $transaction) {
            fwrite($file, json_encode($transaction) . ",\n");
        }

        fclose($file);
    }
}
