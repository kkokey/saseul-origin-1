<?php

namespace Saseul\Util;

class Memcache
{
    protected $mem;

    protected $prefix;
    protected $host;
    protected $port;

    protected $isAlive = true;

    public function __construct()
    {
        $this->initialize();

        $this->mem = new \Memcache();
        $this->mem->addServer($this->host, $this->port) || die('There is no memcached. ');
        $this->mem->setFailureCallback(function () {
            $this->isAlive = false;
        });
    }

    public function initialize()
    {

    }

    public function isConnect()
    {
        if (empty($this->stats())) {
            return $this->isAlive;
        }

        return $this->isAlive;
    }

    public function set($key, $value, $time = 0)
    {
        $this->mem->set($this->prefix.$key, $value, false, $time);

        return $value;
    }

    public function get($key)
    {
        return $this->mem->get($this->prefix.$key);
    }

    public function delete($key): bool
    {
        return $this->mem->delete($this->prefix.$key);
    }

    public function getQueue($queue)
    {
        $tail = $this->mem->get('HT_'.$queue);
        $head = $this->mem->get('HQ_'.$queue);
        $items = [];

        for ($i = $head; $i <= $tail; $i++) {
            $items[] = $this->mem->get('HI_'.$queue.'_'.$i);
        }

        return $items;
    }

    public function enqueue($queue, $item)
    {
        $id = $this->mem->increment($this->prefix.'HT_'.$queue);

        if ($id === false) {
            if ($this->mem->add($this->prefix.'HT_'.$queue,1) === false) {
                $id = $this->mem->increment($this->prefix.'HT_'.$queue);
                if ($id === false) {
                    return false;
                }
            } else {
                $id = 1;
                $this->mem->add($this->prefix.'HQ_'.$queue, $id);
            }
        }

        if ($this->mem->add($this->prefix.'HI_'.$queue.'_'.$id, $item) === false) {
            return false;
        }

        return $id;
    }

    public function dequeue($queue)
    {
        $tail = $this->mem->get($this->prefix.'HT_'.$queue);

        if (($id = $this->mem->increment($this->prefix.'HQ_'.$queue)) === false) {
            return false;
        }

        if (($id - 1) <= $tail) {
            return $this->mem->get($this->prefix.'HI_'.$queue.'_'.($id - 1));
        }
        $this->mem->decrement($this->prefix.'HQ_'.$queue);

        return false;
    }

    public function stats()
    {
        return $this->mem->getStats();
    }

    public function flush()
    {
        $this->mem->flush();
    }
}
