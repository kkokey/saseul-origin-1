<?php

namespace Saseul\Data;

use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\Structure;
use Saseul\System\Database;
use Saseul\Util\Parser;

class Block
{
    public static function lastBlock(): array
    {
        $opt = ['sort' => ['timestamp' => -1]];
        $block = self::data(MongoDbConfig::NAMESPACE_BLOCK, [], $opt);

        return $block;
    }

    public static function lastBlocks(int $max_count = 100): array
    {
        $opt = ['sort' => ['timestamp' => -1]];

        return self::datas(MongoDbConfig::NAMESPACE_BLOCK, $max_count, [], $opt);
    }

    public static function getByNumber(int $block_number)
    {
        $query = ['block_number' => $block_number];
        $block = self::data(MongoDbConfig::NAMESPACE_BLOCK, $query);

        return $block;
    }

    public static function getByRange(int $start_block_number, int $end_block_number, int $max_count = 256)
    {
        $opt = ['sort' => ['timestamp' => 1]];
        $query = ['block_number' => [
            '$gte' => $start_block_number,
            '$lte' => $end_block_number
        ]];

        $blocks = self::datas(MongoDbConfig::NAMESPACE_BLOCK, $max_count, $query, $opt);

        return $blocks;
    }

    public static function data(string $namespace, array $query = [], array $opt = []): array
    {
        $block = Structure::BLOCK;

        $blocks = self::datas($namespace, 1, $query, $opt);

        if (isset($blocks[0])) {
            $block = $blocks[0];
        }

        return $block;
    }

    public static function datas(string $namespace, int $max_count, array $query = [], array $opt = []): array
    {
        $db = Database::GetInstance();
        $rs = $db->Query($namespace, $query, $opt);
        $datas = [];

        foreach ($rs as $item) {
            $item = Parser::objectToArray($item);

            $datas[] = [
                'block_number' => (int)$item['block_number'] ?? 0,
                'last_blockhash' => $item['last_blockhash'] ?? '',
                'blockhash' => $item['blockhash'] ?? '',
                'transaction_count' => (int)$item['transaction_count'] ?? 0,
                's_timestamp' => (int)$item['s_timestamp'] ?? 0,
                'timestamp' => (int)$item['timestamp'] ?? 0,
                'public_key' => $item['public_key'] ?? '',
                'signature' => $item['signature'] ?? '',
            ];

            if (count($datas) >= $max_count) {
                break;
            }
        }

        return $datas;
    }
}