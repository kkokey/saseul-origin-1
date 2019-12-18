<?php

namespace Custom\Contract;

use Custom\Status\S2;
use Saseul\Common\Contract;
use Saseul\Constant\Decision;
use Saseul\Core\Key;
use Saseul\Util\TypeChecker;
use Saseul\Version;

class C5 extends Contract
{
    public const TYPE = 'SendCoin';
    public const T_STRUCTURE = [
        'type' => '',
        'version' => '',
        'from' => '',
        'timestamp' => 0,
        'to' => '',
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
    private $to;
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
        $this->to = $this->transaction['to'] ?? '';
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
        S2::GetInstance()->loadBalance($this->from);
    }

    public function _makeDecision(): string
    {
        $from_balance = S2::GetInstance()->getBalance($this->from);

        if (bccomp($this->amount, '0') === 1
            && bccomp('1000000000000000000000000', $this->amount) >= 0
            && bccomp($from_balance, $this->amount) >= 0
        ) {
            return Decision::ACCEPT;
        }

        return Decision::REJECT;
    }

    public function _setStatus(): void
    {
        # sub & save
        $from_balance = S2::GetInstance()->getBalance($this->from);
        $from_balance = bcsub($from_balance, $this->amount);
        S2::GetInstance()->setBalance($this->from,  $from_balance);

        # add & save
        $to_balance = S2::GetInstance()->getBalance($this->to);
        $to_balance = bcadd($to_balance, $this->amount);
        S2::GetInstance()->setBalance($this->to, $to_balance);
    }
}
