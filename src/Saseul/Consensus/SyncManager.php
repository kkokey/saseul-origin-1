<?php

namespace Saseul\Consensus;

use Saseul\Constant\Directory;
use Saseul\Constant\Structure;
use Saseul\Util\Logger;
use Saseul\Util\Parser;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

class SyncManager
{
    private static $instance = null;

    private $rest;

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getBunchFile($host, $blockNumber)
    {
        $urlGz = "http://{$host}/bunchfile?block_number={$blockNumber}";
        $tmpGz = Directory::TMP_BUNCH;
        file_put_contents($tmpGz, file_get_contents($urlGz));

        if (mime_content_type($tmpGz) === 'application/x-gzip') {
            return $tmpGz;
        } else {
            unlink($tmpGz);
        }

        return '';
    }

    public function getBlockFile($host, $blockNumber) {
        $urlJson = "http://{$host}/blockfile?block_number={$blockNumber}";

        $blockContent = file_get_contents($urlJson);
        $blockContent = '[' . preg_replace('/\,*?$/', '', $blockContent) . ']';
        $blockContent = json_decode($blockContent, true);

        if (empty($blockContent)) {
            $blockContent = [];
        }

        return $blockContent;
    }

    public function makeTempBunch($txArchive)
    {
        $tmpFolder = Directory::TEMP;
        $cmd = "tar -xvzf {$txArchive} -C {$tmpFolder} ";
        shell_exec($cmd);
        usleep(10000);

        return $tmpFolder;
    }

    public function bunchChunks($tmpFolder)
    {
        $chunkFiles = [];
        $chunkTimes = [];

        foreach (scandir($tmpFolder) as $item) {
            if (preg_match("/\.json$/", $item)) {
                $chunkFiles[] = $item;
                $chunkTimes[] = mb_substr($item, 64, mb_strpos($item, '.') - 64);
            }
        }

        array_multisort($chunkTimes, $chunkFiles);

        return $chunkFiles;
    }

    public function netBlockInfo($nodes, $myRoundNumber)
    {
        $execTimes = [];
        $blockInfos = [];
        $hosts = [];

        foreach ($nodes as $node) {
            $hosts[] = $node['host'];
        }

        $results = $this->rest->MultiPOST($hosts, 'blockinfo', ['block_number' => $myRoundNumber]);

        foreach ($results as $item) {
            $r = json_decode($item['result'], true);

            if (!TypeChecker::StructureCheck(Structure::API_BLOCK_INFO, $r)) {
                continue;
            }

            if ($r['data']['blockhash'] === '') {
                continue;
            }

            $blockInfos[] = [
                'host' => $item['host'],
                'exec_time' => $item['exec_time'],
                'last_blockhash' => $r['data']['last_blockhash'],
                'blockhash' => $r['data']['blockhash'],
                's_timestamp' => $r['data']['s_timestamp'],
                'public_key' => $r['data']['public_key'],
                'signature' => $r['data']['signature'],
            ];

            $execTimes[] = $item['exec_time'];
        }

        array_multisort($execTimes, $blockInfos);

        return $blockInfos;
    }

    public function netBunchInfo($nodes, $myRoundNumber)
    {
        $execTimes = [];
        $bunchInfos = [];
        $hosts = [];

        foreach ($nodes as $node) {
            $hosts[] = $node['host'];
        }

        $results = $this->rest->MultiPOST($hosts, 'bunchinfo', ['block_number' => $myRoundNumber]);

        foreach ($results as $item) {
            $r = json_decode($item['result'], true);

            if (!TypeChecker::StructureCheck(Structure::API_BUNCH_INFO, $r)) {
                continue;
            }

            if ($r['data']['file_exists'] === false || $r['data']['final_blockhash'] === '') {
                continue;
            }

            $bunchInfos[] = [
                'host' => $item['host'],
                'exec_time' => $item['exec_time'],
                'blockhash' => $r['data']['blockhash'],
                'final_blockhash' => $r['data']['final_blockhash'],
                'blocks' => $r['data']['blocks'],
            ];

            $execTimes[] = $item['exec_time'];
        }

        array_multisort($execTimes, $bunchInfos);

        return $bunchInfos;
    }
}
