<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Rule;
use Saseul\Data\Block;
use Saseul\Data\Chunk;

class BunchInfo extends Api
{
    function main()
    {
        $block_number = $this->getParam($_REQUEST, 'block_number');
        $file_exists = is_file(Chunk::txArchive($block_number));
        $last_bunch = Rule::bunchFinalNumber($block_number);
        $block = Block::getByNumber($block_number);
        $final_block = Block::getByNumber($last_bunch);
        $blocks = Block::getByRange($block_number, $last_bunch);

        $this->data = [
            'file_exists' => $file_exists,
            'blockhash' => $block['blockhash'],
            'final_blockhash' => $final_block['blockhash'],
            'blocks' => $blocks
        ];
    }
}