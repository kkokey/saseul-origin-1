<?php

namespace Saseul\System;

use Saseul\Core\Env;
use Saseul\Util\Memcache;
use Saseul\Util\Memcached;

/**
 * Database provides DB initialization function and a getter function for the
 * singleton Database instance.
 */
if (class_exists('Memcached')) {
    class Cache extends Memcached
    {
        protected static $instance = null;

        public function initialize()
        {
            $this->prefix = Env::$memcached['prefix'];
            $this->host = Env::$memcached['host'];
            $this->port = Env::$memcached['port'];
        }

        public static function GetInstance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }
    }
} else {
    class Cache extends Memcache
    {
        protected static $instance = null;

        public function initialize()
        {
            $this->prefix = Env::$memcached['prefix'];
            $this->host = Env::$memcached['host'];
            $this->port = Env::$memcached['port'];
        }

        public static function GetInstance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }
    }
}
