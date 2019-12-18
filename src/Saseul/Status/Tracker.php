<?php

namespace Saseul\Status;

use Saseul\Common\Status;
use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\NodeStatus;
use Saseul\Constant\Role;
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
    private $reset = false;

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->_reset();
    }

    public function _reset(): void
    {
        $this->addresses = [];
        $this->items = [];
        $this->reset = false;
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
        if ($this->reset === true)
        {
            $this->db->bulk->delete([]);
            $this->db->BulkWrite(MongoDbConfig::NAMESPACE_TRACKER);
        }

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

    public function load(string $address): void
    {
        $this->addresses[] = $address;
    }

    public function resetRequest()
    {
        $this->reset = true;
    }

    public function setValidator(string $address, string $status = NodeStatus::ADMITTED): void
    {
        $this->items[$address] = [
            'address' => $address,
            'role' => Role::VALIDATOR,
            'status' => $status
        ];
    }

    public function setLightNode(string $address, string $status = NodeStatus::ADMITTED): void
    {
        $this->items[$address] = [
            'address' => $address,
            'role' => Role::LIGHT,
            'status' => $status
        ];
    }

    public function setSupervisor(string $address, string $status = NodeStatus::ADMITTED): void
    {
        $this->items[$address] = [
            'address' => $address,
            'role' => Role::SUPERVISOR,
            'status' => $status
        ];
    }

    public function setArbiter(string $address, string $status = NodeStatus::ADMITTED): void
    {
        $this->items[$address] = [
            'address' => $address,
            'role' => Role::ARBITER,
            'status' => $status
        ];
    }
}