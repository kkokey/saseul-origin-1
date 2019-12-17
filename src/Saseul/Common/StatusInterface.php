<?php

namespace Saseul\Common;

interface StatusInterface
{
    public static function GetInstance();

    public function _setup(): void;

    public function _reset(): void;

    public function _load(): void;

    public function _preprocess(): void;

    public function _save(): void;

    public function _postprocess(): void;
}
