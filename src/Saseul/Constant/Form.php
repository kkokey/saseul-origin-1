<?php

namespace Saseul\Constant;

class Form
{
    const CONTRACT = 'contract';
    const REQUEST = 'request';
    const STATUS = 'status';

    const FORMS = [
        self::CONTRACT,
        self::REQUEST,
        self::STATUS,
    ];

    public static function isExist($form)
    {
        return in_array($form, self::FORMS);
    }
}
