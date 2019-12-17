<?php

namespace Saseul\Consensus;

use Saseul\Constant\Event;
use Saseul\Constant\Structure;
use Saseul\Core\Env;
use Saseul\Core\Key;
use Saseul\Core\Rule;
use Saseul\Data\Block;
use Saseul\Core\IMLog;
use Saseul\Core\Property;
use Saseul\Data\Chunk;
use Saseul\Data\Tracker;
use Saseul\Committer\RequestManager;
use Saseul\Util\DateTime;
use Saseul\Util\Merkle;
use Saseul\Util\Parser;
use Saseul\Util\TypeChecker;

class HAP
{
    private static $instance = null;

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private $round_manager;
    private $sync_manager;
    private $chunk_manager;
    private $hash_manager;
    private $commit_manager;

    public $alive_nodes;
    public $alive_validators;

    private $smtime = 0;
    private $use_bunch = true;

    private $result = Event::NOTHING;
    private $ban_fail_count = 0;
    private $exclude_fail_count = 0;
    private $subject_host = '';
    private $subject_address = '';

    private $last_block;
    private $expect_block;
    private $txs;

    public function __construct()
    {
        $this->round_manager = RoundManager::GetInstance();
        $this->sync_manager = SyncManager::GetInstance();
        $this->commit_manager = CommitManager::GetInstance();
        $this->chunk_manager = ChunkManager::GetInstance();
        $this->hash_manager = HashManager::GetInstance();

        $this->init();
    }

    public function init(): void
    {
        $this->smtime = DateTime::microtime();
        $this->last_block = Block::lastBlock();
        $this->expect_block = Block::lastBlock();
        $this->result = Event::NOTHING;
    }

    public function round(): void
    {
        $this->smtime = DateTime::microtime();

        # start;
        IMLog::add("[Round] start; {$this->etime()} s ");

        # rounding; network check;
        $peers = Tracker::getPeers();
        $validators = Tracker::getAccessibleValidators();

        # make round, collect round;
        $my_round = $this->round_manager->myRound($this->last_block);
        $net_round = $this->round_manager->netRound($peers, $this->last_block);
        $round_info = $this->round_manager->roundInfo($my_round, $net_round, $this->last_block);

        $round_key = $round_info['round_key'] ?? '';

        # check alive nodes;
        $this->alive_nodes = $this->aliveNodes($peers, array_keys($net_round));
        $this->alive_validators = $this->aliveNodes($validators, array_keys($net_round));

        # end;
        Property::peer($peers);
        Property::aliveNode($this->alive_nodes);
        Property::aliveValidator($this->alive_validators);
        Property::chunkInfo($round_key, []);

        IMLog::add("[Round] end; {$this->etime()} s ");
    }

    public function networking(): void
    {
        if (count(Tracker::getAccessibleValidators()) > 2 && count($this->alive_validators) === 1) {
            $this->result = Event::ALONE;
            return;
        }

        $this->dataPulling();

        if ($this->result === Event::NOTHING) {
            return;
        }

        $this->preCommit();

        for ($i = 0; $i < 3; $i++) {
            if ($this->result === Event::DATA_DIFFERENT) {
                $this->dataPulling();
                $this->preCommit();
            }
        }

        if ($this->result === Event::SUCCESS)
        {
            $this->commit();
        }
    }

    public function dataPulling(): void
    {
        # start;
        IMLog::add("[Data Pulling] start; {$this->etime()} s ");

        # round check;
        $round_info = Property::roundInfo();

        $round_number = $round_info['my_round_number'];
        $last_blockhash = $round_info['last_blockhash'];
        $last_s_timestamp = $round_info['last_s_timestamp'];
        $expect_s_timestamp = $round_info['net_s_timestamp'];
        $round_key = $round_info['round_key'];

        if ($expect_s_timestamp === 0 || $last_s_timestamp >= $expect_s_timestamp) {
            IMLog::add("[Data Pulling] nothing; {$this->etime()} s ");
            $this->result = Event::NOTHING;
            return;
        }

        $this->chunk_manager->collectApiChunk(
            $this->alive_validators, $last_s_timestamp, $expect_s_timestamp, $round_key);

        $this->chunk_manager->collectBroadcastChunk(
            $this->alive_validators, $expect_s_timestamp, $round_key);

        # tmp hash info;
        $this->hash_manager->initHashInfo(
            $expect_s_timestamp, $round_number, $last_blockhash, $round_key);

        # success;
        IMLog::add("[Data Pulling] success; {$this->etime()} s ");
        $this->result = Event::SUCCESS;
    }

    public function preCommit(): void
    {
        # start;
        IMLog::add("[Pre-commit] start; {$this->etime()} s ");

        # round check;
        $round_info = Property::roundInfo();

        $last_blockhash = $round_info['last_blockhash'];
        $last_s_timestamp = $round_info['last_s_timestamp'];
        $expect_s_timestamp = $round_info['net_s_timestamp'];
        $round_number = $round_info['my_round_number'];
        $round_leader = $round_info['net_round_leader'];
        $round_key = $round_info['round_key'];

        $transactions = $this->commit_manager->mergedTransactions(
            $last_s_timestamp, $expect_s_timestamp, $round_key);

        if (count($transactions) === 0) {
            IMLog::add("[Pre-commit] no txs; {$this->etime()} s ");
            $this->setSubjectAddress($round_leader);
            $this->result = Event::TRIGGER_LYING;
            return;
        }

        # make decision;
        $completed_txs = $this->commit_manager->completeTransactions($transactions);
        $tx_count = count($completed_txs);
        IMLog::add("[Pre-commit] completed tx count: {$tx_count}; {$this->etime()} s ");

        # make expect block info;
        $txCount = count($completed_txs);
        $my_blockhash = Merkle::MakeBlockHash(
            $last_blockhash, Merkle::MakeMerkleHash($completed_txs), $expect_s_timestamp);

        $expect_block = $this->commit_manager->nextBlock(
            $round_number, $last_blockhash, $my_blockhash, $txCount, $expect_s_timestamp,
            Env::getPublicKey(), Key::makeSignature($my_blockhash, Env::getPrivateKey(), Env::getPublicKey())
        );

        # complete hash info;
        $my_hash_info = $this->hash_manager->myHashInfo(
            $expect_block, $round_number, $last_blockhash, $round_key);

        $net_hash_info = $this->hash_manager->netHashInfo(
            $round_key, $this->alive_validators);

        $best_hash_info = $this->hash_manager->bestHashInfo($my_hash_info, $net_hash_info);
        $expect_blockhash = $best_hash_info['blockhash'];
        $hash_leader = $best_hash_info['address'];

        # need data pulling;
        if ($expect_blockhash === '') {
            $this->result = Event::DATA_DIFFERENT;
            return;
        }

        # need wait;
        if ($expect_blockhash !== $my_blockhash) {
            $this->setSubjectAddress($hash_leader);
            $this->result = Event::HASH_DIFFERENT;
            return;
        }

        # success;
        IMLog::add("[Pre-commit] success; {$this->etime()} s ");
        $this->txs = $completed_txs;
        $this->expect_block = $expect_block;
        $this->result = Event::SUCCESS;
    }

    public function commit(): void
    {
        # start;
        IMLog::add("[Commit] start; {$this->etime()} s ");
        $commit_result = $this->commit_manager->commit($this->txs, $this->last_block, $this->expect_block);

        if ($commit_result === false)
        {
            $this->result = Event::NOTHING;
            return;
        }

        # start;
        IMLog::add("[Commit] end; {$this->etime()} s ");
        $this->commit_manager->makeTransactionChunk($this->expect_block, $this->txs);
        $this->result = Event::SUCCESS;
    }

    public function sync(): void
    {
        IMLog::add("[Sync] start; {$this->etime()} s ");

        $round_info = Property::roundInfo();
        $net_round_number = $round_info['net_round_number'];
        $my_round_number = $round_info['my_round_number'];

        $net_bunch = Rule::bunchFinalNumber($net_round_number);
        $my_bunch = Rule::bunchFinalNumber($my_round_number);

        if ($net_bunch !== $my_bunch && $this->use_bunch) {
            $this->syncBunch();
        } else {
            $this->syncBlock();
        }

        IMLog::add("[Sync] end; {$this->etime()} s ");
    }

    public function finishingWork(): void
    {
        IMLog::add("[FinishingWork] {$this->result} ");

        switch ($this->result)
        {
            case Event::BUNCH_FAIL:
                $this->bunchDisable();
                break;
            case Event::BLOCK_FAIL:
                $this->exclude();
                break;
            case Event::TRIGGER_LYING:
                $this->ban();
                break;
            case Event::NOTHING:
            case Event::ALONE:
                usleep(Rule::MICROINTERVAL_OF_CHUNK);
                break;
            case Event::SUCCESS:
            default:
                $this->success();
                break;
        }

//        IMLog::add('[Debug] '. json_encode([
//            'use_bunch' => $this->use_bunch,
//            'ban_fail_count' => $this->ban_fail_count,
//            'exclude_fail_count' => $this->exclude_fail_count,
//            'subject_host' => $this->subject_host,
//            'subject_address' => $this->subject_address,
//        ]));

        IMLog::add("[FinishingWork] end; {$this->etime()} s ");
        IMLog::add('[Memory] usage: '.(int)(memory_get_usage(true)/1000000).'M');
        IMLog::add("#############################################################");
    }

    public function syncBlock(): void
    {
        # start;
        IMLog::add("[Sync Block] start; {$this->etime()} s ");
        $last_block = Block::lastBlock();
        $round_info = Property::roundInfo();
        $my_round_number = $round_info['my_round_number'];

        $net_block_info = $this->sync_manager->netBlockInfo($this->alive_nodes, $my_round_number);

        if (count($net_block_info) === 0) {
            IMLog::add('[Sync] net_block_info false: nothing; ');
            $this->result = Event::NOTHING;
            return;
        }

        $target = Parser::findMostItem($net_block_info, 'blockhash');

        if ($target['item'] === []) {
            IMLog::add('[Sync] net_block_info false: nothing; ');
            $this->result = Event::NOTHING;
            return;
        }

        $block_info = $target['item'];
        $host = $block_info['host'];

        $transactions = $this->sync_manager->getBlockFile($host, $my_round_number);
        $this->syncCommit($transactions, $last_block, $block_info);

        if ($this->result === Event::BLOCK_FAIL) {
            # prepare exclude;
            IMLog::add("[Sync] subject host: {$host} ");
            $this->setSubjectHost($host);
        }
    }

    public function findBlock($blockhash, $blocks)
    {
        foreach ($blocks as $block)
        {
            if (!TypeChecker::structureCheck(Structure::BLOCK, $block)) {
                continue;
            }

            if ($block['blockhash'] === $blockhash) {
                return $block;
            }
        }

        return null;
    }

    public function syncBunch(): void
    {
        # start;
        IMLog::add("[Sync Bunch] start; {$this->etime()} s ");

        $round_info = Property::roundInfo();
        $my_round_number = $round_info['my_round_number'];
        $net_bunch_info = $this->sync_manager->netBunchInfo($this->alive_nodes, $my_round_number);

        if (count($net_bunch_info) === 0) {
            IMLog::add('[Sync] net_bunch_info false: bunch fail; ');
            $this->result = Event::BUNCH_FAIL;
            return;
        }

        $target = Parser::findMostItem($net_bunch_info, 'final_blockhash');

        if ($target['item'] === []) {
            IMLog::add('[Sync] net_bunch_info false: nothing; ');
            $this->result = Event::NOTHING;
            return;
        }

        $bunch_info = $target['item'];
        $host = $bunch_info['host'];
        $next_blockhash = $bunch_info['blockhash'];
        $blocks = $bunch_info['blocks'];

        $bunch = $this->sync_manager->getBunchFile($host, $my_round_number);

        if ($bunch === '') {
            IMLog::add('[Sync] getBunchFile false: bunch fail; ');
            $this->result = Event::BUNCH_FAIL;
            return;
        }

        $tempBunch = $this->sync_manager->makeTempBunch($bunch);
        $chunks = $this->sync_manager->bunchChunks($tempBunch);

        $first = true;

        foreach ($chunks as $chunk) {
            $lastBlock = Block::lastBlock();
            $transactions = Chunk::getChunk("{$tempBunch}/{$chunk}");
            unlink("{$tempBunch}/{$chunk}");

            $fileBlockhash = mb_substr($chunk, 0, 64);

            # find first;
            if ($first === true && $next_blockhash !== $fileBlockhash) {
                continue;
            }

            $first = false;

            # commit-manager init;
            $expect_block = $this->findBlock($fileBlockhash, $blocks);

            if ($expect_block === null) {
                $this->result = Event::BUNCH_FAIL;
                return;
            }

            IMLog::add("[Sync Bunch] block {$expect_block['block_number']} commit; {$this->etime()} s ");
            $this->syncCommit($transactions, $lastBlock, $expect_block);

            if ($this->result === Event::BLOCK_FAIL) {
                $this->result = Event::BUNCH_FAIL;
                return;
            }
        }

        $this->result = Event::SUCCESS;
    }

    public function syncCommit(array $transactions, array $last_block, array $block_info): void
    {
        $last_s_timestamp = $last_block['s_timestamp'];
        $last_blockhash = $last_block['blockhash'];
        $expect_blockhash = $block_info['blockhash'];
        $expect_s_timestamp = $block_info['s_timestamp'];
        $expect_block_number = (int) $last_block['block_number'] + 1;

        $expect_block = $this->commit_manager->nextBlock(
            $expect_block_number, $last_blockhash, $expect_blockhash, count($transactions),
            $expect_s_timestamp, $block_info['public_key'], $block_info['signature']
        );

        # commit-manager init;
        # merge & sort;
        $completed_txs = $this->commit_manager->orderedTransactions($transactions, $last_s_timestamp, $expect_s_timestamp);
        $completed_txs = $this->commit_manager->completeTransactions($completed_txs);

        # make expect block info;
        $myBlockhash = Merkle::MakeBlockHash($last_blockhash, Merkle::MakeMerkleHash($completed_txs), $expect_s_timestamp);

        if ($expect_blockhash === $myBlockhash) {
            $commit_result = $this->commit_manager->commit($completed_txs, $last_block, $expect_block);

            if ($commit_result === true)
            {
                $this->commit_manager->makeTransactionChunk($expect_block, $transactions);

                # ok;
                $this->result = Event::SUCCESS;
                return;
            }
        }

        # banish;
        IMLog::add('[Sync] syncCommit false; block fail; ');

        $this->result = Event::BLOCK_FAIL;
    }

    public function forceCommit(array $transactions, int $expect_s_timestamp)
    {
        $last_block = Block::lastBlock();
        $last_block_number = $last_block['block_number'];
        $last_s_timestamp = $last_block['s_timestamp'];
        $last_blockhash = $last_block['blockhash'];
        $next_block_number = (int) $last_block_number + 1;

        # commit-manager init;
        # merge & sort;
        $completed_txs = $this->commit_manager->orderedTransactions($transactions, $last_s_timestamp, $expect_s_timestamp);
        $completed_txs = $this->commit_manager->completeTransactions($completed_txs);

        # make expect block info;
        $txCount = count($completed_txs);

        if ($txCount > 0) {

            $expect_blockhash = Merkle::MakeBlockHash(
                $last_blockhash, Merkle::MakeMerkleHash($completed_txs), $expect_s_timestamp
            );

            $public_key = Env::getPublicKey();
            $signature = Key::makeSignature($expect_blockhash, Env::getPrivateKey(), Env::getPublicKey());

            $nextBlock = $this->commit_manager->nextBlock(
                $next_block_number, $last_blockhash, $expect_blockhash, $txCount, $expect_s_timestamp,
                $public_key, $signature
            );

            $commit_result = $this->commit_manager->commit($completed_txs, $last_block, $nextBlock, true);

            if ($commit_result === true)
            {
                $this->commit_manager->makeTransactionChunk($nextBlock, $transactions);
            }
        }
    }

    public function localRequest($type, $request, $rhash, $public_key, $signature)
    {
        $request_manager = RequestManager::GetInstance();
        $request_manager->initializeRequest($type, $request, $rhash, $public_key, $signature);
        $validity = $request_manager->getRequestValidity();

        if ($validity === false)
        {
            return $request_manager->getMessage();
        }

        return $request_manager->getResponse();
    }

    public function aliveNodes(array $nodes, array $alives)
    {
        $alive_nodes = [];

        foreach ($nodes as $node) {
            if (in_array($node['address'], $alives)) {
                $alive_nodes[] = $node;
            }
        }

        return $alive_nodes;
    }

    private function etime()
    {
        return ((DateTime::microtime() - $this->smtime) / 1000000);
    }

    private function exclude(): void
    {
        if ($this->exclude_fail_count >= 5)
        {
            IMLog::add("[Exclude] host: {$this->subject_host} ");
            Tracker::excludeRequest($this->subject_host);
            $this->exclude_fail_count = 0;
            return;
        }

        $this->exclude_fail_count++;
    }

    private function ban(): void
    {
        if ($this->ban_fail_count >= 5)
        {
            IMLog::add("[Ban] address: {$this->subject_address} ");
            Tracker::ban($this->subject_address);
            $this->ban_fail_count = 0;
            return;
        }

        $this->ban_fail_count++;
    }

    private function checkUseBunch(): void
    {
        $last_block_number = $this->last_block['block_number'] ?? 0;

        if ($last_block_number % Rule::BUNCH === 0) {
            $this->use_bunch = true;
        }
    }

    private function bunchDisable(): void
    {
        $this->use_bunch = false;
    }

    private function resetFailCount(): void
    {
        $this->subject_host = '';
        $this->subject_address = '';
        $this->exclude_fail_count = 0;
        $this->ban_fail_count = 0;
    }

    private function setSubjectHost(string $host): void
    {
        $this->subject_host = $host;
    }

    private function setSubjectAddress(string $address): void
    {
        $this->subject_address = $address;
    }

    private function success(): void
    {
        $this->checkUseBunch();
        $this->resetFailCount();
    }
}