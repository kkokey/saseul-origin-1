<?php

namespace Saseul\Constant;

class Structure
{
    public const ENV = [
        'user' => '',
        'group' => '',
        'memcached' => [
            'host' => '',
            'port' => 0,
            'prefix' => '',
        ],
        'mongo_db' => [
            'host' => '',
            'port' => 0
        ],
        'node' => [
            'address' => '',
            'public_key' => '',
            'private_key' => ''
        ],
        'genesis' => [
            'address' => '',
        ]
    ];

    public const BLOCK = [
        'block_number' => 0,
        'last_blockhash' => '',
        'blockhash' => '',
        'transaction_count' => 0,
        's_timestamp' => 0,
        'timestamp' => 0,
        'public_key' => '',
        'signature' => '',
    ];

    public const GENERATION = [
        'origin_blockhash' => '',
        'final_blockhash' => '',
    ];

    public const ROUND = [
        'decision' => [
            'round_number' => 0,
            'last_blockhash' => '',
            'last_s_timestamp' => 0,
            'timestamp' => 0,
            'round_key' => '',
            'expect_s_timestamp' => 0
        ],
        'public_key' => '',
        'hash' => '',
        'signature' => ''
    ];

    public const BROADCAST_RESULT = [
        'items' => [],
        'address' => '',
        'broadcast_code' => '',
    ];

    public const BROADCAST_ITEM = [
        'address' => '',
        'transactions' => [],
        'public_key' => '',
        'content_signature' => '',
    ];

    public const TX_ITEM = [
        'transaction' => [
            'type' => '',
            'timestamp' => 0,
        ],
        'signature' => '',
        'public_key' => '',
    ];

    public const HASH_INFO = [
        'decision' => [
            'round_number' => 0,
            'last_blockhash' => '',
            'blockhash' => '',
            'transaction_count' => 0,
            's_timestamp' => 0,
            'timestamp' => 0,
            'round_key' => '',
            'chunk_info' => [],
        ],
        'public_key' => '',
        'hash' => '',
        'signature' => '',
    ];

    public const API_BLOCK_INFO = [
        'status' => '',
        'data' => [
            'last_blockhash' => '',
            'blockhash' => '',
            's_timestamp' => 0,
            'public_key' => '',
            'signature' => '',
        ]
    ];

    public const API_BUNCH_INFO = [
        'status' => '',
        'data' => [
            'file_exists' => true,
            'blockhash' => '',
            'final_blockhash' => '',
            'blocks' => [],
        ]
    ];

    public const API_GENERATION_INFO = [
        'status' => '',
        'data' => [
            'file_exists' => true,
            'origin_blockhash' => '',
            'final_blockhash' => '',
        ]
    ];
}