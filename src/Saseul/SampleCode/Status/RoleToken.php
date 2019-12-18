<?php

namespace Custom\Status;

use Saseul\Common\Status;
use Saseul\Constant\MongoDbConfig;
use Saseul\System\Database;

class S1 extends Status
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

    public $dbname = MongoDbConfig::DB_CUSTOM;
    public $collection = 'role_token';

    private $addresses = [];
    private $token_names = [];
    private $balances = [];
    private $exists = false;

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->_reset();
    }

    public function _setup(): void
    {
        $rs = $this->db->Command($this->dbname,
            ['listCollections' => true, 'filter' => ['name' => $this->collection]]);

        $exists = false;

        foreach ($rs as $item) {
            $exists = true;
        }

        if (!$exists) {
            $this->db->Command($this->dbname, ['create' => $this->collection]);
            $this->db->Command($this->dbname, [
                'createIndexes' => $this->collection,
                'indexes' => [
                    ['key' => ['address' => 1], 'name' => 'address_asc'],
                    ['key' => ['token_name' => 1], 'name' => 'token_name_asc'],
                    ['key' => ['address' => 1, 'token_name' => 1], 'name' => 'address_token_name_asc', 'unique' => 1],
                ]
            ]);
        }

    }

    public function _reset(): void
    {
        $this->addresses = [];
        $this->token_names = [];
        $this->balances = [];
        $this->exists = false;
    }

    public function _load(): void
    {
        $this->_loadExists();
        $this->_loadBalance();
    }

    private function _loadExists(): void
    {
        $command = ['count' => $this->collection];
        $rs = $this->db->Command($this->dbname, $command);
        $count = 0;

        foreach ($rs as $item) {
            $count = (int)$item->n;
            break;
        }

        if ($count > 0) {
            $this->exists = true;
        }
    }

    private function _loadBalance(): void
    {
        $this->addresses = array_values(array_unique($this->addresses));
        $this->token_names = array_values(array_unique($this->token_names));

        if (count($this->addresses) === 0) {
            return;
        }

        $namespace = $this->dbname.'.'.$this->collection;
        $filter = [
            'address' => ['$in' => $this->addresses],
            'token_name' => ['$in' => $this->token_names],
        ];
        $rs = $this->db->Query($namespace, $filter);

        foreach ($rs as $item) {
            if (isset($item->balance)) {
                $this->balances[$item->address][$item->token_name] = $item->balance;
            }
        }
    }

    public function _save(): void
    {
        $namespace = $this->dbname.'.'.$this->collection;

        foreach ($this->balances as $address => $item) {
            foreach ($item as $token_name => $balance) {
                $filter = ['address' => $address, 'token_name' => $token_name];
                $row = [
                    '$set' => [
                        'balance' => $balance,
                    ],
                ];
                $opt = ['upsert' => true];
                $this->db->bulk->update($filter, $row, $opt);
            }
        }

        if ($this->db->bulk->count() > 0) {
            $this->db->BulkWrite($namespace);
        }

        self::_reset();
    }

    public function loadToken(string $address, string $token_name): void
    {
        $this->addresses[] = $address;
        $this->token_names[] = $token_name;
    }

    public function getBalance(string $address, string $token_name): string
    {
        if (isset($this->balances[$address][$token_name])) {
            return $this->balances[$address][$token_name];
        }

        return '0';
    }

    public function getExists(): bool
    {
        return $this->exists;
    }

    public function setBalance(string $address, string $token_name, string $value): void
    {
        $this->balances[$address][$token_name] = $value;
    }
}
