<?php

namespace Saseul\Common;

interface RequestInterface
{
    public function _init(array $request, string $rhash, string $public_key, string $signature): void;

    public function _getValidity(): bool;

    public function _getResponse(): array;

    public function _getMessage();
}
