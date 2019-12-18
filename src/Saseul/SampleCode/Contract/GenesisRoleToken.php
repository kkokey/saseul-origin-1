<?php

namespace Custom\Contract;

use Custom\Status\S1;
use Saseul\Common\Contract;
use Saseul\Constant\Decision;
use Saseul\Constant\Title;
use Saseul\Core\Key;
use Saseul\Status\Authority;
use Saseul\Status\Tracker;
use Saseul\Util\TypeChecker;
use Saseul\Version;

class C1 extends Contract
{
    public const TYPE = 'GenesisRoleToken';
    public const T_STRUCTURE = [
        'type' => '',
        'version' => '',
        'from' => '',
        'timestamp' => 0,
        'validator_amount' => '',
        'network_manager_amount' => '',
    ];

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    private $type;
    private $version;
    private $from;
    private $timestamp;
    private $validator_amount;
    private $network_manager_amount;

    private $validator_token_name = 'vt';
    private $network_manager_token_name = 'nmt';

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
        $this->validator_amount = $this->transaction['validator_amount'] ?? '';
        $this->network_manager_amount = $this->transaction['network_manager_amount'] ?? '';

        $this->timestamp = (int) $this->timestamp;
        $this->validator_amount = preg_replace('/\..*$/', '', $this->validator_amount);
        $this->network_manager_amount = preg_replace('/\..*$/', '', $this->network_manager_amount);
    }

    public function _getValidity(): bool
    {
        if (!TypeChecker::structureCheck(self::T_STRUCTURE, $this->transaction)) {
            return false;
        }

        return Version::isValid($this->version)
            && is_numeric($this->timestamp)
            && $this->type === self::TYPE
            && bccomp($this->validator_amount, '0') === 1
            && bccomp($this->network_manager_amount, '0') === 1
            && bccomp('1000', $this->validator_amount) >= 0
            && bccomp('10', $this->network_manager_amount) >= 0
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _loadStatus(): void
    {
        Authority::GetInstance()->loadAuthority($this->from, Title::NETWORK_MANAGER);
    }

    public function _makeDecision(): string
    {
        $exists = S1::GetInstance()->getExists();
        $is_manager = Authority::GetInstance()->getAuthority($this->from, Title::NETWORK_MANAGER);

        if ($exists === false && $is_manager === true
            && bccomp($this->validator_amount, '0') === 1
            && bccomp($this->network_manager_amount, '0') === 1
            && bccomp('1000', $this->validator_amount) >= 0
            && bccomp('10', $this->network_manager_amount) >= 0
        ) {
            return Decision::ACCEPT;
        }

        return Decision::REJECT;
    }

    public function _setStatus(): void
    {
        S1::GetInstance()->setBalance($this->from, $this->validator_token_name, $this->validator_amount);
        S1::GetInstance()->setBalance($this->from, $this->network_manager_token_name, $this->network_manager_amount);
        Tracker::GetInstance()->resetRequest();
        Tracker::GetInstance()->setValidator($this->from);
        Authority::GetInstance()->resetAuthority(Title::NETWORK_MANAGER);
        Authority::GetInstance()->setAuthority($this->from, Title::NETWORK_MANAGER);
    }
}
