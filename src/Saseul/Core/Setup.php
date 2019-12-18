<?php

namespace Saseul\Core;

use Saseul\Constant\Directory;
use Saseul\Constant\MongoDbConfig;
use Saseul\System\Cache;
use Saseul\System\Database;
use Saseul\Util\File;
use Saseul\Util\Logger;

class Setup
{
    private $db;
    private $cache;

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->cache = Cache::GetInstance();
    }

    public function DeleteFiles()
    {
        Logger::log('Delete: blockdata');
        File::rrmdir(Directory::BLOCKDATA);

        Logger::log('Delete: contractdata');
        File::rrmdir(Directory::CONTRACTDATA);

        Logger::log('Delete: temp folder');
        File::rrmdir(Directory::TEMP);

        Logger::log('Delete: debug log');
        if (is_file(Directory::DEBUG_LOG_FILE)) {
            unlink(Directory::DEBUG_LOG_FILE);
        }
    }

    public function FlushCache()
    {
        Logger::log('Flush Cache');
        $this->cache->flush();
    }

    public function DropDatabase()
    {
        Logger::log('Drop Database');
        $this->db->Command(MongoDbConfig::DB_SASEUL, ['dropDatabase' => 1]);
        $this->db->Command(MongoDbConfig::DB_CUSTOM, ['dropDatabase' => 1]);
        $this->db->Command(MongoDbConfig::DB_TEST, ['dropDatabase' => 1]);
    }

    public function CreateDatabase()
    {
        Logger::log('Create Database');

        Logger::log('Create Collections: blocks');
        $this->db->Command(MongoDbConfig::DB_SASEUL, ['create' => MongoDbConfig::COLLECTION_BLOCK]);
        Logger::log('Create Collections: transactions');
        $this->db->Command(MongoDbConfig::DB_SASEUL, ['create' => MongoDbConfig::COLLECTION_TRANSACTION]);
        Logger::log('Create Collections: generations');
        $this->db->Command(MongoDbConfig::DB_SASEUL, ['create' => MongoDbConfig::COLLECTION_GENERATION]);
        Logger::log('Create Collections: tracker');
        $this->db->Command(MongoDbConfig::DB_SASEUL, ['create' => MongoDbConfig::COLLECTION_TRACKER]);
        Logger::log('Create Collections: peer');
        $this->db->Command(MongoDbConfig::DB_SASEUL, ['create' => MongoDbConfig::COLLECTION_PEER]);
        Logger::log('Create Collections: known_hosts');
        $this->db->Command(MongoDbConfig::DB_SASEUL, ['create' => MongoDbConfig::COLLECTION_KNOWN_HOSTS]);
    }

    public function CreateIndex()
    {
        Logger::log('Create Index');

        $this->db->Command(MongoDbConfig::DB_SASEUL, [
            'createIndexes' => MongoDbConfig::COLLECTION_BLOCK,
            'indexes' => [
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['block_number' => 1], 'name' => 'block_number_asc'],
                ['key' => ['block_number' => -1], 'name' => 'block_number_desc'],
                ['key' => ['blockhash' => 1], 'name' => 'blockhash_asc'],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_SASEUL, [
            'createIndexes' => MongoDbConfig::COLLECTION_TRANSACTION,
            'indexes' => [
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['timestamp' => 1, 'thash' => 1], 'name' => 'unique', 'unique' => 1],
                ['key' => ['thash' => 1], 'name' => 'thash_asc'],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_SASEUL, [
            'createIndexes' => MongoDbConfig::COLLECTION_GENERATION,
            'indexes' => [
                ['key' => ['origin_block_number' => 1], 'name' => 'origin_block_number_unique', 'unique' => 1],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_SASEUL, [
            'createIndexes' => MongoDbConfig::COLLECTION_TRACKER,
            'indexes' => [
                ['key' => ['address' => 1], 'name' => 'address_unique', 'unique' => 1],
                ['key' => ['role' => 1], 'name' => 'role_asc'],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_SASEUL, [
            'createIndexes' => MongoDbConfig::COLLECTION_PEER,
            'indexes' => [
                ['key' => ['host' => 1], 'name' => 'host_unique', 'unique' => 1],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_SASEUL, [
            'createIndexes' => MongoDbConfig::COLLECTION_KNOWN_HOSTS,
            'indexes' => [
                ['key' => ['host' => 1], 'name' => 'host_unique', 'unique' => 1],
            ]
        ]);
    }
}
