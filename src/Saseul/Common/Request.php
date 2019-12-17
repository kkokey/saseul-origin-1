<?php

namespace Saseul\Common;

class Request implements RequestInterface
{
    public function _init(array $request, string $rhash, string $public_key, string $signature): void
    {
    }

    public function _getValidity(): bool
    {
        return false;
    }

    public function _getResponse(): array
    {
        return [];
    }

    public function _getMessage()
    {
        return 'ok';
    }
}
