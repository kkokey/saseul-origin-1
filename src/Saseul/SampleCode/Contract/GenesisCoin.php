<?php

namespace Custom\Contract;

use Custom\Status\S2;
use Saseul\Common\Contract;
use Saseul\Constant\Decision;
use Saseul\Constant\Title;
use Saseul\Core\Key;
use Saseul\Status\Authority;
use Saseul\Util\TypeChecker;
use Saseul\Version;

class C4 extends Contract
{
    public const TYPE = 'GenesisCoin';
    public const T_STRUCTURE = [
        'type' => '',
        'version' => '',
        'from' => '',
        'timestamp' => 0,
        'amount' => '',
    ];

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    private $type;
    private $version;
    private $from;
    private $timestamp;
    private $amount;

    public function _init($transaction, $thash, $public_key, $signature): void
    {
        $this->transaction = $transaction;
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        $this->type = $this->transaction['type'] ?? '';
        $this->version = $this->transaction['version'] ?? '';
        $this->from = $this->transaction['from'] ?? '';
        $this->timestamp = $this->transaction['timestamp'] ?? 0;
        $this->amount = $this->transaction['amount'] ?? '';

        $this->timestamp = (int) $this->timestamp;
        $this->amount = preg_replace('/\..*$/', '', $this->amount);
    }

    public function _getValidity(): bool
    {
        if (!TypeChecker::structureCheck(self::T_STRUCTURE, $this->transaction)) {
            return false;
        }

        return Version::isValid($this->version)
            && is_numeric($this->timestamp)
            && $this->type === self::TYPE
            && bccomp($this->amount, '0') === 1
            && bccomp('1000000000000000000000000', $this->amount) >= 0
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _loadStatus(): void
    {
        Authority::GetInstance()->loadAuthority($this->from, Title::NETWORK_MANAGER);
    }

    public function _makeDecision(): string
    {
        $exists = S2::GetInstance()->getExists();
        $is_manager = Authority::GetInstance()->getAuthority($this->from, Title::NETWORK_MANAGER);

        if ($exists === false && $is_manager === true
            && bccomp($this->amount, '0') === 1
            && bccomp('1000000000000000000000000', $this->amount) >= 0
        ) {
            return Decision::ACCEPT;
        }

        return Decision::REJECT;
    }

    public function _setStatus(): void
    {
        S2::GetInstance()->setBalance($this->from, $this->amount);
    }
}
