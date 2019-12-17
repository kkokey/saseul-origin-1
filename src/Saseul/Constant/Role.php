<?php

namespace Saseul\Constant;

class Role
{
    const VALIDATOR = 'validator';
    const SUPERVISOR = 'supervisor';
    const ARBITER = 'arbiter';
    const LIGHT = 'light';

    const ROLES = [
        self::VALIDATOR,
        self::SUPERVISOR,
        self::ARBITER,
        self::LIGHT,
    ];

    public static function isExist($role)
    {
        return in_array($role, self::ROLES);
    }
}
