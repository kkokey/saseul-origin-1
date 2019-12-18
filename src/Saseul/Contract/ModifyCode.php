<?php

namespace Saseul\Contract;

use Saseul\Common\Contract;
use Saseul\Constant\Decision;
use Saseul\Constant\Title;
use Saseul\Core\Key;
use Saseul\Core\Rule;
use Saseul\Status\Authority;
use Saseul\Status\Code;
use Saseul\Util\TypeChecker;
use Saseul\Version;

class ModifyCode extends Contract
{
    public const TYPE = 'ModifyCode';
    public const T_STRUCTURE = [
        'type' => '',
        'version' => '',
        'from' => '',
        'timestamp' => 0,
        'code' => '',
        'form' => '',
        'cid' => '',
    ];

    public const CONTRACT = 'contract';
    public const REQUEST = 'request';
    public const STATUS = 'status';

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    private $type;
    private $version;
    private $from;
    private $timestamp;

    private $code;
    private $form;
    private $cid;

    public function _init(array $transaction, string $thash, string $public_key, string $signature): void
    {
        $this->transaction = $transaction;
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        $this->type = $this->transaction['type'] ?? '';
        $this->version = $this->transaction['version'] ?? '';
        $this->from = $this->transaction['from'] ?? '';
        $this->timestamp = $this->transaction['timestamp'] ?? 0;

        $this->code = $this->transaction['code'] ?? '';
        $this->form = $this->transaction['form'] ?? '';
        $this->cid = $this->transaction['cid'] ?? '';

        $this->timestamp = (int) $this->timestamp;
    }

    public function _getValidity(): bool
    {
        if (!TypeChecker::structureCheck(self::T_STRUCTURE, $this->transaction)) {
            return false;
        }

        return Version::isValid($this->version)
            && $this->type === self::TYPE
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature)
            && Rule::isValidCID($this->cid, $this->form);
    }

    public function _loadStatus(): void
    {
        Authority::GetInstance()->loadAuthority($this->from, Title::NETWORK_MANAGER);
        Code::GetInstance()->load($this->cid);
    }

    public function _makeDecision(): string
    {
        $is_manager = Authority::GetInstance()->getAuthority($this->from, Title::NETWORK_MANAGER);

        $code = Code::GetInstance()->get($this->cid);

        if ($code !== null) {
            $is_exists = true;
        } else {
            $is_exists = false;
        }

        if ($is_exists === true && $is_manager === true) {
            return Decision::ACCEPT;
        }

        return Decision::REJECT;
    }

    public function _setStatus(): void
    {
        $item = [
            'code' => $this->code,
            'form' => $this->form
        ];

        Code::GetInstance()->set($this->cid, $item);
        Code::GetInstance()->reloadSignal(true);
   }
}