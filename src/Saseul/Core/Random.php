<?php

namespace Saseul\Core;

class Random
{
    public static $seed = '';

    public static function getSeed(): string
    {
        return self::$seed;
    }

    public static function setSeed($hash): void
    {
        self::$seed = $hash;
    }

    public static function reset(): void
    {
        self::$seed = '';
    }
}