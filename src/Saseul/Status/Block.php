<?php

namespace Saseul\Status;

use Saseul\Common\Status;
use Saseul\Constant\MongoDbConfig;
use Saseul\System\Database;

class Block extends Status
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
    private $count;

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->_reset();
    }

    public function _reset(): void
    {
        $this->count = 0;
    }

    public function _load(): void
    {
        $command = [
            'count' => MongoDbConfig::COLLECTION_BLOCK,
        ];

        $rs = $this->db->Command(MongoDbConfig::DB_SASEUL, $command);

        foreach ($rs as $item) {
            $this->count = (int)$item->n;
            break;
        }
    }

    public function getCount(): int
    {
        return $this->count;
    }
}