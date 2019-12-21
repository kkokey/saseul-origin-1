<?php

namespace Saseul\Request;

use Saseul\Constant\MongoDbConfig;
use Saseul\Common\Request;
use Saseul\System\Database;
use Saseul\Util\Parser;

class ListTransaction extends Request
{
    public const TYPE = 'ListTransaction';

    protected $msg = 'ok';

    protected $request;
    protected $rhash;
    protected $public_key;
    protected $signature;

    private $type;
    private $version;
    private $from;
    private $timestamp;
    private $page;
    private $count;
    private $sort;

    public function _init(array $request, string $rhash, string $public_key, string $signature): void
    {
        $this->request = $request;
        $this->rhash = $rhash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        $this->type = $this->request['type'] ?? '';
        $this->version = $this->request['version'] ?? '';
        $this->from = $this->request['from'] ?? '';
        $this->timestamp = $this->request['timestamp'] ?? 0;
        $this->page = $this->request['page'] ?? 1;
        $this->count = $this->request['count'] ?? 20;
        $this->sort = $this->request['count'] ?? -1;

        $this->timestamp = (int) $this->timestamp;
        $this->page = (int) $this->page;
        $this->count = (int) $this->count;
        $this->sort = (int) $this->sort;
    }

    public function _getValidity(): bool
    {
        return in_array($this->sort, [1, -1])
            && ($this->page > 0)
            && ($this->count > 0);
    }

    public function _getResponse(): array
    {
        $db = Database::GetInstance();
        $skip = ($this->page - 1) * $this->count;
        $limit = $this->count;

        $namespace = MongoDbConfig::NAMESPACE_TRANSACTION;
        $opt = [
            'skip' => $skip,
            'limit' => $limit,
            'sort' => ['timestamp' => $this->sort]
        ];
        $rs = $db->Query($namespace, [], $opt);
        $items = [];

        foreach ($rs as $item) {
            if (isset($item->_id)) {
                unset($item->_id);
            }

            $items[] = Parser::objectToArray($item);
        }

        return $items;
    }

    public function _getMessage()
    {
        return $this->msg;
    }
}
