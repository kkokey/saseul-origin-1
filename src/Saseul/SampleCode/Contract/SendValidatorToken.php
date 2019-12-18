<?php

namespace Custom\Contract;

use Custom\Status\S1;
use Saseul\Common\Contract;
use Saseul\Constant\Decision;
use Saseul\Core\Key;
use Saseul\Status\Tracker;
use Saseul\Util\TypeChecker;
use Saseul\Version;

class C2 extends Contract
{
    public const TYPE = 'SendValidatorToken';
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

    private $validator_token_name = 'vt';

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
            && bccomp('1000', $this->amount) >= 0
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _loadStatus(): void
    {
        S1::GetInstance()->loadToken($this->from, $this->validator_token_name);
        S1::GetInstance()->loadToken($this->to, $this->validator_token_name);
    }

    public function _makeDecision(): string
    {
        $from_balance = S1::GetInstance()->getBalance($this->from, $this->validator_token_name);

        if (bccomp($this->amount, '0') === 1
            && bccomp('1000', $this->amount) >= 0
            && bccomp($from_balance, $this->amount) >= 0
        ) {
            return Decision::ACCEPT;
        }

        return Decision::REJECT;
    }

    public function _setStatus(): void
    {
        # sub & save
        $from_balance = S1::GetInstance()->getBalance($this->from, $this->validator_token_name);
        $from_balance = bcsub($from_balance, $this->amount);
        S1::GetInstance()->setBalance($this->from, $this->validator_token_name, $from_balance);

        # add & save
        $to_balance = S1::GetInstance()->getBalance($this->to, $this->validator_token_name);
        $to_balance = bcadd($to_balance, $this->amount);
        S1::GetInstance()->setBalance($this->to, $this->validator_token_name, $to_balance);

        # from role
        $from_balance = S1::GetInstance()->getBalance($this->from, $this->validator_token_name);

        if (bccomp($from_balance, '0') === 0) {
            Tracker::GetInstance()->setLightNode($this->from);
        }

        # to role
        $to_balance = S1::GetInstance()->getBalance($this->to, $this->validator_token_name);

        if (bccomp($to_balance, '0') === 1) {
            Tracker::GetInstance()->setValidator($this->to);
        }
    }
}
