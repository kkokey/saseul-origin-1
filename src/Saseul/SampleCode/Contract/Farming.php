<?php

namespace Custom\Contract;

use Custom\Status\S2;
use Saseul\Common\Contract;
use Saseul\Constant\Decision;
use Saseul\Core\Key;
use Saseul\Util\TypeChecker;
use Saseul\Version;

class C6 extends Contract
{
    public const TYPE = 'Farming';
    public const T_STRUCTURE = [
        'type' => '',
        'version' => '',
        'from' => '',
        'timestamp' => 0,
    ];

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    private $type;
    private $version;
    private $from;
    private $timestamp;

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

        $this->timestamp = (int) $this->timestamp;
    }

    public function _getValidity(): bool
    {
        if (!TypeChecker::structureCheck(self::T_STRUCTURE, $this->transaction)) {
            return false;
        }

        return Version::isValid($this->version)
            && is_numeric($this->timestamp)
            && $this->type === self::TYPE
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _makeDecision(): string
    {
        return Decision::ACCEPT;
    }

    public function _setStatus(): void
    {
        S2::GetInstance()->addFarmer($this->from);
    }
}
