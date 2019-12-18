<?php

namespace Custom\Status;

use Saseul\Common\Status;
use Saseul\Constant\MongoDbConfig;
use Saseul\Core\Random;
use Saseul\System\Database;
use Saseul\Util\Logger;

class S2 extends Status
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
    public $collection = 'coin';

    private $addresses = [];
    private $balances = [];
    private $deposits = [];
    private $farmers = [];
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
        $this->farmers = [];
        $this->exists = false;
    }

    public function _load(): void
    {
        $this->_loadExists();
        $this->_loadBalanceAndDeposit();
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

    private function _loadBalanceAndDeposit(): void
    {
        $this->addresses = array_values(array_unique($this->addresses));

        if (count($this->addresses) === 0) {
            return;
        }

        $namespace = $this->dbname.'.'.$this->collection;
        $filter = ['address' => ['$in' => $this->addresses]];
        $rs = $this->db->Query($namespace, $filter);

        foreach ($rs as $item) {
            if (isset($item->balance)) {
                $this->balances[$item->address] = $item->balance;
            }

            if (isset($item->deposit)) {
                $this->deposits[$item->address] = $item->deposit;
            }
        }
    }

    public function _preprocess(): void
    {
        $this->farmers = array_values(array_unique($this->farmers));
        $coin = $this->farmedAmount(Random::getSeed());

        foreach ($this->farmers as $address)
        {
            $farmer_balance = $this->getBalance($address);
            $farmer_balance = bcadd($farmer_balance, $coin);
            $this->setBalance($address, $farmer_balance);
        }
    }

    public function _save(): void
    {
        $db = Database::GetInstance();
        $namespace = $this->dbname.'.'.$this->collection;

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
            $db->BulkWrite($namespace);
        }

        self::_reset();
    }

    private function farmedAmount(string $seed): string
    {
        $a = ['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'];
        $c = 1;
        $final = 0;

        for ($i = mb_strlen($seed) - 1; $i >= 0; $i--) {
            $hex = $seed[$i];
            $b = array_search($hex, $a);
            if ($b < 0) {
                $b = 0;
            }
            $final = bcadd($final, bcmul($b, $c));
            $c = bcmul($c, 16);
        }

        $coin = (string)(bcmod($final, 10) + 1);

        return $coin;
    }

    public function loadBalance(string $address): void
    {
        $this->addresses[] = $address;
    }

    public function loadDeposit(string $address): void
    {
        $this->addresses[] = $address;
    }

    public function getBalance(string $address): string
    {
        if (isset($this->balances[$address])) {
            return $this->balances[$address];
        }

        return '0';
    }

    public function getDeposit(string $address): string
    {
        if (isset($this->deposits[$address])) {
            return $this->deposits[$address];
        }

        return '0';
    }

    public function getExists(): bool
    {
        return $this->exists;
    }

    public function setBalance(string $address, string $value): void
    {
        $this->balances[$address] = $value;
    }

    public function setDeposit(string $address, string $value): void
    {
        $this->deposits[$address] = $value;
    }

    public function addFarmer(string $address): void
    {
        $this->farmers[] = $address;
    }
}
