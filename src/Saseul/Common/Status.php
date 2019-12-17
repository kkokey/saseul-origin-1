<?php

namespace Saseul\Common;

class Status implements StatusInterface
{
    protected static $instance = null;

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function _setup(): void {}

    public function _reset(): void {}

    public function _load(): void {}

    public function _preprocess(): void {}

    public function _save(): void {}

    public function _postprocess(): void {}
}
