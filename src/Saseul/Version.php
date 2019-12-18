<?php

namespace Saseul;

class Version
{
    const CURRENT = '1.1.0.21';
    const LENGTH_LIMIT = 64;

    public static function isValid($version)
    {
        return is_string($version) && !empty($version) && (mb_strlen($version) < self::LENGTH_LIMIT);
    }
}
