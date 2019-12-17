<?php

namespace Saseul\Status;

use Saseul\Common\Status;
use Saseul\Constant\Directory;
use Saseul\Constant\Form;
use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\Signal;
use Saseul\Core\Property;
use Saseul\System\Database;

class Code extends Status
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
    private $reload_signal = false;
    private $cids = [];
    private $items = [];

    public function __construct()
    {
        $this->db = Database::GetInstance();
        $this->_reset();
    }

    public function _setup(): void
    {
        $rs = $this->db->Command(MongoDbConfig::DB_SASEUL,
            ['listCollections' => true, 'filter' => ['name' => MongoDbConfig::COLLECTION_CODE]]);

        $exists = false;

        foreach ($rs as $item) {
            $exists = true;
        }

        if (!$exists) {
            $this->db->Command(MongoDbConfig::DB_SASEUL, ['create' => MongoDbConfig::COLLECTION_CODE]);
            $this->db->Command(MongoDbConfig::DB_SASEUL, [
                'createIndexes' => MongoDbConfig::COLLECTION_CODE,
                'indexes' => [
                    ['key' => ['cid' => 1], 'name' => 'cid_asc', 'unique' => 1],
                    ['key' => ['form' => 1], 'name' => 'form_asc'],
                ]
            ]);
        }
    }

    public function _reset(): void
    {
        $this->cids = [];
        $this->items = [];
    }

    public function _load(): void
    {
        $this->cids = array_values(array_unique($this->cids));

        if (count($this->cids) === 0) {
            return;
        }

        $filter = ['cid' => ['$in' => $this->cids]];
        $rs = $this->db->Query(MongoDbConfig::NAMESPACE_CODE, $filter);

        foreach ($rs as $item) {
            if (isset($item->form) && isset($item->code)) {
                $this->items[$item->cid] = [
                    'form' => $item->form,
                    'code' => $item->code
                ];
            }
        }
    }

    public function _save(): void
    {
        foreach ($this->items as $k => $v)
        {
            $form = $v['form'] ?? '';
            $code = $v['code'] ?? '';

            if (!Form::isExist($form) || $code === '')
            {
                continue;
            }

            $filter = ['cid' => $k];
            $row = [
                '$set' => $v,
            ];
            $opt = ['upsert' => true];

            $this->db->bulk->update($filter, $row, $opt);

            switch ($form)
            {
                case Form::CONTRACT:
                    $filename = Directory::CUSTOM_CONTRACT.DIRECTORY_SEPARATOR.$k.'.php';
                    file_put_contents($filename, $code);
                    break;
                case Form::REQUEST:
                    $filename = Directory::CUSTOM_REQUEST.DIRECTORY_SEPARATOR.$k.'.php';
                    file_put_contents($filename, $code);
                    break;
                case Form::STATUS:
                    $filename = Directory::CUSTOM_STATUS.DIRECTORY_SEPARATOR.$k.'.php';
                    file_put_contents($filename, $code);
                    break;
            }
        }

        if ($this->db->bulk->count() > 0) {
            $this->db->BulkWrite(MongoDbConfig::NAMESPACE_CODE);
        }

        if ($this->reload_signal === true) {
            Property::daemonSig(Signal::RELOAD);
        }

        $this->_reset();
    }

    public function load($cid) {
        $this->cids[] = $cid;
    }

    public function get($cid)
    {
        $code = $this->items[$cid] ?? null;

        return $code;
    }

    public function set($cid, $item)
    {
        $this->items[$cid] = $item;
    }

    public function reloadSignal(bool $signal = true)
    {
        $this->reload_signal = $signal;
    }
}