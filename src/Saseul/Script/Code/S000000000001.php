<?php

namespace Custom\Status;

use Saseul\Common\Status;
use Saseul\System\Database;

class S000000000001 extends Status
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
    private $dbname = 'saseul_custom';
    private $namespace = 'saseul_custom.coin';
    private $collection = 'coin';

    private $addresses = [];
    private $balances = [];
    private $deposits = [];

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
                    ['key' => ['address' => 1], 'name' => 'address_unique', 'unique' => 1],
                ]
            ]);
        }

    }

    public function _reset(): void
    {
        $this->addresses = [];
        $this->balances = [];
        $this->deposits = [];
    }

    public function _load(): void
    {
        $this->addresses = array_values(array_unique($this->addresses));

        if (count($this->addresses) === 0) {
            return;
        }

        $filter = ['address' => ['$in' => $this->addresses]];
        $rs = $this->db->Query($this->namespace, $filter);

        foreach ($rs as $item) {
            if (isset($item->balance)) {
                $this->balances[$item->address] = $item->balance;
            }

            if (isset($item->deposit)) {
                $this->deposits[$item->address] = $item->deposit;
            }
        }
    }

    public function _save(): void
    {
        $db = Database::GetInstance();

        foreach ($this->balances as $k => $v) {
            $filter = ['address' => $k];
            $row = [
                '$set' => ['balance' => $v],
            ];
            $opt = ['upsert' => true];
            $db->bulk->update($filter, $row, $opt);
        }

        foreach ($this->deposits as $k => $v) {
            $filter = ['address' => $k];
            $row = [
                '$set' => ['deposit' => $v],
            ];
            $opt = ['upsert' => true];
            $db->bulk->update($filter, $row, $opt);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite($this->namespace);
        }

        self::_reset();
    }

    public function loadBalance($address)
    {
        $this->addresses[] = $address;
    }

    public function loadDeposit($address)
    {
        $this->addresses[] = $address;
    }

    public function getBalance($address)
    {
        if (isset($this->balances[$address])) {
            return $this->balances[$address];
        }

        return '0';
    }

    public function getDeposit($address)
    {
        if (isset($this->deposits[$address])) {
            return $this->deposits[$address];
        }

        return '0';
    }

    public function setBalance($address, string $value)
    {
        $this->balances[$address] = $value;
    }

    public function setDeposit($address, string $value)
    {
        $this->deposits[$address] = $value;
    }
}
