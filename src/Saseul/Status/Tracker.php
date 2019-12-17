<?php

namespace Saseul\Status;

use Saseul\Common\Status;
use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\NodeStatus;
use Saseul\System\Database;
use Saseul\Util\Parser;

class Tracker extends Status
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
    private $addresses;
    private $items;

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->_reset();
    }

    public function _reset(): void
    {
        $this->addresses = [];
        $this->items = [];
    }

    public function _load(): void
    {
        $this->addresses = array_values(array_unique($this->addresses));

        if (count($this->addresses) === 0) {
            return;
        }

        $filter = ['address' => ['$in' => $this->addresses]];
        $rs = $this->db->Query(MongoDbConfig::NAMESPACE_TRACKER, $filter);

        foreach ($rs as $item) {
            if (isset($item->_id)) {
                unset($item->_id);
            }

            $item = Parser::objectToArray($item);
            $this->items[$item->address] = $item;
        }
    }

    public function _save(): void
    {
        foreach ($this->items as $k => $v)
        {
            $filter = ['address' => $k];
            $row = [
                '$set' => $v,
            ];
            $opt = ['upsert' => true];
            $this->db->bulk->update($filter, $row, $opt);
        }

        if ($this->db->bulk->count() > 0) {
            $this->db->BulkWrite(MongoDbConfig::NAMESPACE_TRACKER);
        }

        $this->_reset();
    }

    public function load($address) {
        $this->addresses[] = $address;
    }

    public function setItem($address, string $role = 'light', string $status = NodeStatus::ADMITTED)
    {
        $this->items[$address] = [
            'address' => $address,
            'role' => $role,
            'status' => $status,
        ];
    }
}