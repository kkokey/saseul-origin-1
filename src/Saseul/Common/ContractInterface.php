<?php

namespace Saseul\Common;

interface ContractInterface
{
    public function _init(array $transaction, string $thash, string $public_key, string $signature): void;

    public function _getValidity(): bool;

    public function _loadStatus(): void;

    public function _getStatus(): void;

    public function _makeDecision(): string;

    public function _setStatus(): void;
}
