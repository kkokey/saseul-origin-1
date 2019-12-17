<?php

namespace Custom\Contract;

use Custom\Status\S000000000001;
use Saseul\Common\Contract;
use Saseul\Constant\Account;
use Saseul\Constant\Decision;
use Saseul\Core\Key;
use Saseul\Util\TypeChecker;
use Saseul\Version;

class C000000000001 extends Contract
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

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $to;
    private $amount;
    private $timestamp;

    private $from_balance;

    public function _init($transaction, $thash, $public_key, $signature): void
    {
        $this->transaction = $transaction;
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        if (isset($this->transaction['type'])) {
            $this->type = $this->transaction['type'];
        }
        if (isset($this->transaction['version'])) {
            $this->version = $this->transaction['version'];
        }
        if (isset($this->transaction['from'])) {
            $this->from = $this->transaction['from'];
        }
        if (isset($this->transaction['to'])) {
            $this->to = $this->transaction['to'];
        }
        if (isset($this->transaction['amount'])) {
            $this->amount = $this->transaction['amount'];
            $this->amount = preg_replace('/\..*$/', '', $this->amount);
        }
        if (isset($this->transaction['timestamp'])) {
            $this->timestamp = $this->transaction['timestamp'];
        }
    }

    public function _getValidity(): bool
    {
        if (!TypeChecker::structureCheck(self::T_STRUCTURE, $this->transaction)) {
            return false;
        }

        return Version::isValid($this->version)
            && is_string($this->to)
            && is_numeric($this->amount)
            && is_numeric($this->timestamp)
            && $this->type === self::TYPE
            && (mb_strlen($this->to) === Account::ADDRESS_SIZE)
            && bccomp($this->amount, '0') === 1
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _loadStatus(): void
    {
        S000000000001::GetInstance()->loadBalance($this->from);
        S000000000001::GetInstance()->loadBalance($this->to);
    }

    public function _getStatus(): void
    {
        $this->from_balance = S000000000001::GetInstance()->getBalance($this->from);
    }

    public function _makeDecision(): string
    {
        if (bccomp($this->amount, '0') === 1
        && bccomp($this->from_balance, $this->amount) === 1)
        {
            return Decision::ACCEPT;
        }

        return Decision::REJECT;
    }

    public function _setStatus(): void
    {
        # sub & save
        $from_balance = S000000000001::GetInstance()->getBalance($this->from);
        $from_balance = bcsub($from_balance, $this->amount);
        S000000000001::GetInstance()->setBalance($this->from, $from_balance);

        # add & save
        $to_balance = S000000000001::GetInstance()->getBalance($this->to);
        $to_balance = bcadd($to_balance, $this->amount);
        S000000000001::GetInstance()->setBalance($this->to, $to_balance);
    }
}
