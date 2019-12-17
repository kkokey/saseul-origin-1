<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Data\Chunk;

class BlockFile extends Api
{
    public function main()
    {
        $block_number = (int) $this->getParam($_REQUEST, 'block_number', ['default' => 0]);
        $transactionDir = Chunk::txFullDir($block_number);
        $txFileName = Chunk::txFilename($block_number);

        $this->findJson($transactionDir, $txFileName);
    }

    public function findJson($transactionDir, $txFileName)
    {
        $filePath = "{$transactionDir}/{$txFileName}.json";

        if (is_file($filePath)) {
            $fileSize = filesize($filePath);

            header("Pragma: public");
            header("Expires: 0");
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename={$txFileName}.json");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: {$fileSize}");

            ob_clean();
            flush();
            readfile($filePath);

            exit();
        }
    }
}