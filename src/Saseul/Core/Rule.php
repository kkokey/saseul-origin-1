<?php

namespace Saseul\Core;

use Saseul\Constant\Form;

class Rule
{
    public const BUNCH = 256;
    public const GENERATION = 4096;

    public const MICROINTERVAL_OF_CHUNK = 1000000;
    public const REQUEST_VALIDTIME = 2000000;
    public const PEER_SEARCH = 1000;

    public const TX_LIMIT = 256 * 1024; # 256KB
    public const CHUNK_LIMIT = 1 * 1024 * 1024; # 1MB
    public const BROADCAST_LIMIT = 2 * 1024 * 1024; # 2MB
    public const BLOCK_LIMIT = 10 * 1024 * 1024; # 10MB
    public const API_CHUNK_COUNT_LIMIT = 100;

    public static function bunchFinalNumber(int $round_number): int
    {
        return ($round_number - ($round_number % Rule::BUNCH) + Rule::BUNCH - 1);
    }

    public static function checksum(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function hash($target): string
    {
        if (is_array($target)) {
            return hash('sha256', json_encode($target));
        }

        if (is_string($target)) {
            return hash('sha256', $target);
        }

        return $target;
    }

    public static function isValidCID(string $cid, string $form): bool
    {
        switch ($form) {
            case Form::CONTRACT:
                return preg_match('/^C[0-9a-z]{12,80}$/', $cid);
                break;
            case Form::REQUEST:
                return preg_match('/^R[0-9a-z]{12,80}$/', $cid);
                break;
            case Form::STATUS:
                return preg_match('/^S[0-9a-z]{12,80}$/', $cid);
                break;
            default:
                return false;
                break;
        }
    }

    public static function roundKey(string $block, int $round_number): string
    {
        return hash('ripemd160', $block).$round_number;
    }
}