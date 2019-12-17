<?php

namespace Saseul\Util;

class DateTime
{
    /**
     * This returns the current Unix timestamp with microseconds.
     *
     * @return int indicates the current time
     */
    public static function microtime(): int
    {
        return (int) (array_sum(explode(' ', microtime())) * 1000000);
    }

    /**
     * This returns the current Unix timestamp with milliseconds.
     *
     * @return int indicates the current time
     */
    public static function millitime(): int
    {
        return (int) (array_sum(explode(' ', microtime())) * 1000);
    }

    public static function time(): int
    {
        return time();
    }
}
