<?php

namespace Saseul\Request;

use Saseul\Constant\MongoDbConfig;
use Saseul\Common\Request;
use Saseul\System\Database;
use Saseul\Util\Parser;

class GetTransaction extends Request
{
    public const TYPE = 'GetTransaction';

    protected $msg = 'ok';

    protected $request;
    protected $rhash;
    protected $public_key;
    protected $signature;

    private $type;
    private $version;
    private $from;
    private $find_thash;
    private $timestamp;

    public function _init(array $request, string $rhash, string $public_key, string $signature): void
    {
        $this->request = $request;
        $this->rhash = $rhash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        $this->type = $this->request['type'] ?? '';
        $this->version = $this->request['version'] ?? '';
        $this->from = $this->request['from'] ?? '';
        $this->find_thash = $this->request['thash'] ?? '';
        $this->timestamp = $this->request['timestamp'] ?? 0;
    }

    public function _getValidity(): bool
    {
        return true;
    }

    public function _getResponse(): array
    {
        $db = Database::GetInstance();

        $namespace = MongoDbConfig::NAMESPACE_TRANSACTION;
        $filter = ['thash' => $this->find_thash];
        $opt = ['sort' => ['timestamp' => -1]];
        $rs = $db->Query($namespace, $filter, $opt);

        $transaction = [];

        foreach ($rs as $item) {
            $item = Parser::objectToArray($item);
            unset($item['_id']);

            $transaction = $item;
            break;
        }

        return $transaction;
    }

    public function _getMessage()
    {
        return $this->msg;
    }
}
