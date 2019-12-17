<?php

namespace Saseul\Constant;

class Signal
{
    public const DESTROY = -1;
    public const RUN = 1;
    public const RESTART = 2;
    public const ZOMBIE = 3;
    public const RELOAD = 4;
    public const STOP = 8;
    public const KILL = 9;

    public const DEFAULT_PID = -999;

    public static function isAlive($signal)
    {
        return in_array($signal, [self::RUN, self::RESTART, self::ZOMBIE, self::RELOAD]);
    }
}