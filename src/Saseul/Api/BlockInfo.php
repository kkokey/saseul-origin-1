<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Data\Block;

class BlockInfo extends Api
{
    function main()
    {
        $block_number = $this->getParam($_REQUEST, 'block_number', ['default' => 0]);
        $block = Block::getByNumber($block_number);
        $lastBlock = Block::lastBlock();

        $this->data = [
            'target_block' => $block,
            'last_block' => $lastBlock,
            'last_blockhash' => $block['last_blockhash'],
            'blockhash' => $block['blockhash'],
            's_timestamp' => $block['s_timestamp'],
            'public_key' => $block['public_key'],
            'signature' => $block['signature'],
        ];
    }
}