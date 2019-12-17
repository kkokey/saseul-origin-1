<?php

namespace Saseul\Data;

use Saseul\Core\Rule;

class Generation
{
    public static function originNumber(int $roundNumber)
    {
        $originNumber = ($roundNumber - ($roundNumber % Rule::GENERATION) - 1);

        if ($originNumber < 0) {
            $originNumber = 0;
        }

        return $originNumber;
    }
}