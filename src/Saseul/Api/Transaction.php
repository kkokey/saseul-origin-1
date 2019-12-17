<?php

namespace Saseul\Api;

use Saseul\Committer\ContractManager;
use Saseul\Common\Api;
use Saseul\Constant\HttpStatus;
use Saseul\Core\Env;
use Saseul\Core\Rule;
use Saseul\Data\Chunk;
use Saseul\Data\Tracker;
use Saseul\Util\DateTime;
use Saseul\Util\RestCall;

class Transaction extends Api
{
    protected $rest;
    protected $contract_manager;

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
        $this->contract_manager = new ContractManager();

        if (Chunk::countApiChunk() > Rule::API_CHUNK_COUNT_LIMIT) {
            $msg = 'Service unavailable. ';
            $this->fail(HttpStatus::SERVICE_UNAVAILABLE, $msg);
        }
    }

    public function main()
    {
        $transaction = json_decode($this->getParam($_REQUEST, 'transaction', ['default' => '{}', 'type' => 'string']), true);
        $public_key = $this->getParam($_REQUEST, 'public_key', ['default' => '']);
        $signature = $this->getParam($_REQUEST, 'signature', ['default' => '']);

        $type = $this->getParam($transaction, 'type');
        $timestamp = (int)$this->getParam($transaction, 'timestamp');
        $now = DateTime::microtime();

        if (strlen(json_encode($transaction)) > Rule::TX_LIMIT)
        {
            $this->error('Transaction size is too big. limit: '. Rule::TX_LIMIT. ' bytes. ');
        }

        $thash = Rule::hash($transaction);

        $this->contract_manager->initTransaction($type, $transaction, $thash, $public_key, $signature);
        $validity = $this->contract_manager->getTransactionValidity();

        if ($validity == false) {
            $this->error('Invalid transaction');
        }

        if ($timestamp < $now)
        {
            $this->error('Timestamp must be greater than '. $now);
        }

        if (Tracker::isValidator(Env::getAddress())) {
            $this->addTransaction($transaction, $public_key, $signature, $timestamp);
            $this->data['result'] = 'Transaction is added';
        } else {
            $data = $this->broadcastTransaction($transaction, $public_key, $signature);
            $this->data = $data;
        }
    }

    public function addTransaction($transaction, $public_key, $signature, $timestamp)
    {
        Chunk::saveAPIChunk([
            'transaction' => $transaction,
            'public_key' => $public_key,
            'signature' => $signature,
        ], $timestamp);
    }

    public function broadcastTransaction($transaction, $public_key, $signature)
    {
        $validator = Tracker::getRandomValidator();

        $data = [];

        if (isset($validator['host'])) {
            $host = $validator['host'];

            $url = "http://{$host}/transaction";
            $data = [
                'transaction' => json_encode($transaction),
                'public_key' => $public_key,
                'signature' => $signature,
            ];

            $rs = $this->rest->POST($url, $data);
            $rs = json_decode($rs, true);

            $data = $rs['data'] ?? [];
        }

        return $data;
    }
}
