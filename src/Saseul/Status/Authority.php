<?php

namespace Saseul\Status;

use Saseul\Common\Status;
use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\Title;
use Saseul\System\Database;

class Authority extends Status
{
    protected static $instance = null;

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private $db;
    private $addresses_manager;
    private $managers;

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->_reset();
    }

    public function _setup(): void
    {
        $rs = $this->db->Command(MongoDbConfig::DB_SASEUL,
            ['listCollections' => true, 'filter' => ['name' => MongoDbConfig::COLLECTION_AUTHORITY]]);

        $exists = false;

        foreach ($rs as $item) {
            $exists = true;
        }

        if (!$exists) {
            $this->db->Command(MongoDbConfig::DB_SASEUL, ['create' => MongoDbConfig::COLLECTION_AUTHORITY]);
            $this->db->Command(MongoDbConfig::DB_SASEUL, [
                'createIndexes' => MongoDbConfig::COLLECTION_AUTHORITY,
                'indexes' => [
                    ['key' => ['address' => 1], 'name' => 'address_asc'],
                    ['key' => ['title' => 1], 'name' => 'title_asc'],
                ]
            ]);
        }
    }

    public function _reset(): void
    {
        $this->addresses_manager = [];
        $this->managers = [];
    }

    public function _load(): void
    {
        $this->addresses_manager = array_values(array_unique($this->addresses_manager));

        if (count($this->addresses_manager) === 0) {
            return;
        }

        $filter = ['address' => ['$in' => $this->addresses_manager], 'title' => Title::NETWORK_MANAGER];
        $rs = $this->db->Query(MongoDbConfig::NAMESPACE_AUTHORITY, $filter);

        foreach ($rs as $item) {
            if (isset($item->_id)) {
                unset($item->_id);
            }

            $this->managers[$item->address] = true;
        }
    }

    public function _save(): void
    {
        foreach ($this->managers as $k => $v)
        {
            if ($v === true) {
                $filter = ['address' => $k, 'title' => Title::NETWORK_MANAGER];
                $row = [
                    '$set' => ['title' => Title::NETWORK_MANAGER],
                ];
                $opt = ['upsert' => true];
                $this->db->bulk->update($filter, $row, $opt);
            } else {
                $filter = ['address' => $k, 'title' => Title::NETWORK_MANAGER];
                $this->db->bulk->delete($filter);
            }
        }

        if ($this->db->bulk->count() > 0) {
            $this->db->BulkWrite(MongoDbConfig::NAMESPACE_AUTHORITY);
        }

        $this->_reset();
    }

    public function load($address) {
        $this->addresses_manager[] = $address;
    }

    public function get($address)
    {
        $bool = $this->managers[$address] ?? false;

        return $bool;
    }

    public function setManager($address, $bool = true)
    {
        $this->managers[$address] = $bool;
    }
}