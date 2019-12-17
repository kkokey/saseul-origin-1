<?php

namespace Saseul\Common;

use Saseul\Constant\Decision;

class Contract implements ContractInterface
{
    public function _init(array $transaction, string $thash, string $public_key, string $signature): void {}

    public function _getValidity(): bool
    {
        return false;
    }

    public function _loadStatus(): void {}

    public function _getStatus(): void {}

    public function _makeDecision(): string
    {
        return Decision::REJECT;
    }

    public function _setStatus(): void {}
}
