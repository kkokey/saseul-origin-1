<?php

namespace Saseul\Status;

use Saseul\Common\Status;
use Saseul\Constant\MongoDbConfig;
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
    private $addresses = [];
    private $titles = [];
    private $authorities = [];
    private $reset_titles = [];

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
                    ['key' => ['address' => 1, 'title' => 1], 'name' => 'address_title_asc', 'unique' => 1],
                ]
            ]);
        }
    }

    public function _reset(): void
    {
        $this->addresses = [];
        $this->titles = [];
        $this->authorities = [];
        $this->reset_titles = [];
    }

    public function _load(): void
    {
        $this->addresses = array_values(array_unique($this->addresses));
        $this->titles = array_values(array_unique($this->titles));

        if (count($this->addresses) === 0) {
            return;
        }

        $filter = [
            'address' => ['$in' => $this->addresses],
            'title' => ['$in' => $this->titles],
        ];
        $rs = $this->db->Query(MongoDbConfig::NAMESPACE_AUTHORITY, $filter);

        foreach ($rs as $item) {
            $this->authorities[$item->address][$item->title] = true;
        }
    }

    public function _save(): void
    {
        if (count($this->reset_titles) > 0)
        {
            $filter = [
                'title' => ['$in' => $this->reset_titles]
            ];
            $this->db->bulk->delete($filter);
            $this->db->BulkWrite(MongoDbConfig::NAMESPACE_AUTHORITY);
        }

        foreach ($this->authorities as $address => $item) {
            foreach ($item as $title => $bool) {
                if ($bool === true) {
                    $filter = ['address' => $address, 'title' => $title];
                    $row = [
                        '$set' => [
                            'address' => $address,
                            'title' => $title,
                        ],
                    ];
                    $opt = ['upsert' => true];
                    $this->db->bulk->update($filter, $row, $opt);
                } else {
                    $filter = ['address' => $address, 'title' => $title];
                    $this->db->bulk->delete($filter);
                }
            }
        }

        if ($this->db->bulk->count() > 0) {
            $this->db->BulkWrite(MongoDbConfig::NAMESPACE_AUTHORITY);
        }

        $this->_reset();
    }

    public function loadAuthority(string $address, string $title): void
    {
        $this->addresses[] = $address;
        $this->titles[] = $title;
    }

    public function getAuthority(string $address, string $title): bool
    {
        if (isset($this->authorities[$address][$title])) {
            return $this->authorities[$address][$title];
        }

        return false;
    }

    public function resetAuthority(string $title): void
    {
        $this->reset_titles[] = $title;
    }

    public function setAuthority(string $address, string $title): void
    {
        $this->authorities[$address][$title] = true;
    }

    public function removeAuthority(string $address, string $title): void
    {
        $this->authorities[$address][$title] = false;
    }
}