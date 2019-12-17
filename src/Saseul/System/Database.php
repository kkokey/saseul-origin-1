<?php

namespace Saseul\System;

use Saseul\Core\Env;
use Saseul\Util\MongoDb;

/**
 * Database provides DB initialization function and a getter function for the
 * singleton Database instance.
 */
class Database extends MongoDb
{
    protected static $instance = null;

    /**
     * Initialize the DB.
     */
    public function Init()
    {
        $this->db_host = Env::$mongoDb['host'];
        $this->db_port = Env::$mongoDb['port'];
    }

    /**
     * Return the singleton Database Instance.
     *
     * @return Database The singleton Databse instance.
     */
    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
