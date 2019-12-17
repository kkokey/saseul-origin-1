<?php

namespace Saseul\Data;

use Saseul\Constant\Directory;
use Saseul\Constant\MongoDbConfig;
use Saseul\Core\Env;
use Saseul\Core\Key;
use Saseul\Core\Property;
use Saseul\Core\Rule;
use Saseul\System\Cache;
use Saseul\System\Database;
use Saseul\Util\DateTime;
use Saseul\Util\File;
use Saseul\Util\Logger;
use Saseul\Util\Merkle;

class Chunk
{
    public static function getTID($timestamp)
    {
        $tid = $timestamp - ($timestamp % Rule::MICROINTERVAL_OF_CHUNK)
            + Rule::MICROINTERVAL_OF_CHUNK;

        return $tid;
    }

    public static function getExpectStandardTimestamp($last_s_timestamp)
    {
        $expect_s_timestamp = self::getTID(DateTime::microtime() - Rule::MICROINTERVAL_OF_CHUNK);

        $d = scandir(Directory::API_CHUNKS);

        foreach ($d as $dir) {
            if (!preg_match('/[0-9]+\\.json/', $dir)) {
                continue;
            }

            $tid = preg_replace('/[^0-9]/', '', $dir);

            if ((int) $tid > (int) $last_s_timestamp && (int) $tid < (int) $expect_s_timestamp) {
                # transactions exists;
                return $expect_s_timestamp;
            }
        }

        # not exists;
        return 0;
    }

    public static function getChunk($filename)
    {
        $file = fopen($filename, 'r');
        $contents = fread($file, filesize($filename));
        fclose($file);
        $contents = '[' . preg_replace('/\,*?$/', '', $contents) . ']';

        return json_decode($contents, true);
    }

    public static function txFromApiChunk(int $minTime, int $maxTime): array
    {
        $txs = [];
        $keys = [];
        $chunks = self::chunkList(Directory::API_CHUNKS);

        $len = 0;

        sort($chunks);

        foreach ($chunks as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }

            $time = (int)(pathinfo($filePath)['filename']);

            if ($maxTime >= $time && $time > $minTime) {
                $contents = '[' . preg_replace('/\,*?$/', '', file_get_contents($filePath)) . ']';
                $contents = json_decode($contents, true);

                foreach ($contents as $content) {
                    if (in_array($content['signature'], $keys)) {
                        continue;
                    }

                    $len+= strlen(json_encode($content));

                    if ($len > Rule::CHUNK_LIMIT) {
                        break;
                    }

                    $txs[] = $content;
                    $keys[] = $content['signature'];
                }
            }

            if ($len > Rule::CHUNK_LIMIT) {
                break;
            }
        };

        return $txs;
    }

    public static function broadcastChunk(string $fileName): array
    {
        $broadcastChunk = [];

        $chunks = self::chunkList(Directory::BROADCAST_CHUNKS);

        foreach ($chunks as $filePath)
        {
            if (!is_file($filePath)) {
                continue;
            }

            $item = pathinfo($filePath)['filename'];

            if ($item === $fileName) {
                $contents = file_get_contents($filePath);
                $broadcastChunk = json_decode($contents, true);

                if (empty($broadcastChunk)) {
                    $broadcastChunk = [];
                }

                break;
            }
        }

        return $broadcastChunk;
    }

    public static function broadcastCode(string $round_key)
    {
        $validatorAddress = Tracker::getValidatorAddress();
        $chunk_info = Property::chunkInfo($round_key);
        $collectedAddress = [];
        $broadcastCode = '';

        if (is_array($chunk_info) && count($chunk_info) > 0) {
            $collectedAddress = array_keys($chunk_info);
        }

        sort($validatorAddress);

        foreach ($validatorAddress as $address) {
            if (in_array($address, $collectedAddress)) {
                $broadcastCode.= '1';
            } else {
                $broadcastCode.= '0';
            }
        }

        return $broadcastCode;
    }

    public static function chunkList(string $directory): array
    {
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        foreach (scandir($directory) as $item) {
            if (preg_match('/\.json$/', $item)) {
                $files[] = $directory.DIRECTORY_SEPARATOR.$item;
            }
        }

        return $files;
    }

    public static function countApiChunk(): int
    {
        return count(self::chunkList(Directory::API_CHUNKS));
    }

    public static function contentSignature(string $key, int $timestamp, array $txs): string
    {
        $cache = Cache::GetInstance();
        $sig = $cache->get($key);
        $tx_sigs = [];

        if ($sig === false) {
            foreach ($txs as $tx)
            {
                $tx_sig = $tx['signature'] ?? '';

                if ($tx_sig === '') {
                    continue;
                }

                $tx_sigs[] = $tx_sig;
            }

            $sig = Key::makeSignature(Merkle::MakeMerkleHash($tx_sigs).$timestamp, Env::getPrivateKey(), Env::getPublicKey());
            $cache->set($key, $sig, 10);
        }

        return $sig;
    }

    public static function isValidContentSignaure(string $public_key, int $timestamp, string $content_signature, array $txs): bool
    {
        $tx_sigs = [];

        foreach ($txs as $tx)
        {
            $tx_sig = $tx['signature'] ?? '';

            if ($tx_sig === '') {
                continue;
            }

            $tx_sigs[] = $tx_sig;
        }

        return Key::isValidSignature(Merkle::MakeMerkleHash($tx_sigs).$timestamp, $public_key, $content_signature);
    }

    public static function makeBroadcastChunk(string $filename, string $contents)
    {
        $full_path = Directory::BROADCAST_CHUNKS.DIRECTORY_SEPARATOR.$filename.'.json';

        file_put_contents($full_path, $contents);
    }

    public static function removeAPIChunk(int $s_timestamp)
    {
        if (!is_numeric($s_timestamp)) {
            return;
        }

        $d = scandir(Directory::API_CHUNKS);
        $files = [];

        foreach ($d as $dir) {
            if (!preg_match('/[0-9]+\\.json/', $dir)) {
                continue;
            }

            $tid = preg_replace('/[^0-9]/', '', $dir);

            if ((int)$tid <= (int) $s_timestamp) {
                $files[] = $dir;
            }
        }

        foreach ($files as $file) {
            $filename = Directory::API_CHUNKS.DIRECTORY_SEPARATOR.$file;
            unlink($filename);
        }
    }

    public static function removeBroadcastChunk($round_key)
    {
        $d = scandir(Directory::BROADCAST_CHUNKS);
        $files = [];

        foreach ($d as $dir) {
            if (!preg_match("/^({$round_key}).+(json|key)$/", $dir)) {
                continue;
            }

            $files[] = $dir;
        }

        foreach ($files as $file) {
            $filename = Directory::BROADCAST_CHUNKS.DIRECTORY_SEPARATOR.$file;
            unlink($filename);
        }
    }

    public static function txFilename(int $block_number)
    {
        $block = Block::getByNumber($block_number);

        if (isset($block['blockhash']) && isset($block['s_timestamp'])) {
            return $block['blockhash'].$block['s_timestamp'];
        }

        return '';
    }

    public static function txSubDir(int $block_number): string
    {
        $hex = str_pad(dechex($block_number), 12, '0', STR_PAD_LEFT);
        $dir = [
            mb_substr($hex, 0, 2),
            mb_substr($hex, 2, 2),
            mb_substr($hex, 4, 2),
            mb_substr($hex, 6, 2),
            mb_substr($hex, 8, 2),
        ];

        return implode(DIRECTORY_SEPARATOR, $dir);
    }

    public static function txFullDir(int $block_number): string
    {
        return Directory::TRANSACTIONS.DIRECTORY_SEPARATOR.self::txSubDir($block_number);
    }

    public static function txArchive(int $block_number): string
    {
        return Directory::TX_ARCHIVE.DIRECTORY_SEPARATOR.self::txSubDir($block_number).'.tar.gz';
    }

    public static function makeTxSubDir(int $block_number): bool
    {
        $subdir = Directory::TRANSACTIONS;
        $dir = explode(DIRECTORY_SEPARATOR, self::txSubDir($block_number));
        $make = false;

        foreach ($dir as $item) {
            $subdir = $subdir.DIRECTORY_SEPARATOR.$item;

            if (!file_exists($subdir)) {
                mkdir($subdir);
                chmod($subdir, 0775);
                $make = true;
            }
        }

        return $make;
    }

    public static function makeTxArchive($block_number)
    {
        $full_dir = self::txSubDir($block_number);
        $target = Directory::TRANSACTIONS.DIRECTORY_SEPARATOR.$full_dir;
        $output = Directory::TX_ARCHIVE.DIRECTORY_SEPARATOR.$full_dir . '.tar.gz';
        $subdir = Directory::TX_ARCHIVE;
        $dir = explode(DIRECTORY_SEPARATOR, $full_dir);

        array_pop($dir);

        if (!is_dir($target)) {
            return;
        }

        foreach ($dir as $item) {
            $subdir = $subdir.DIRECTORY_SEPARATOR.$item;

            if (!file_exists($subdir)) {
                mkdir($subdir);
                chmod($subdir, 0775);
            }
        }

        if (!is_file($output)) {
            $targetDir = scandir($target);
            $files = [];

            foreach ($targetDir as $item) {
                if (preg_match('/\.json$/', $item)) {
                    $files[] = $item;
                }
            }

            if (count($files) === Rule::BUNCH || ($full_dir === '00'.DIRECTORY_SEPARATOR.'00'.DIRECTORY_SEPARATOR.'00'.DIRECTORY_SEPARATOR.'00'.DIRECTORY_SEPARATOR.'00' && count($files) === (Rule::BUNCH - 1))) {
                $cmd = "tar -cvzf {$output} -C {$target} . ";
                shell_exec($cmd);
            }
        }
    }

    public static function saveAPIChunk($contents, $timestamp)
    {
        $filename = Directory::API_CHUNKS.DIRECTORY_SEPARATOR.self::getTID($timestamp).'.json';

        $sign = false;

        if (!is_file($filename)) {
            $sign = true;
        }

        $file = fopen($filename, 'a');
        fwrite($file, json_encode($contents) . ",\n");
        fclose($file);

        if ($sign) {
            chmod($filename, 0775);
        }
    }

    public static function removeOldBlock(int $lastBlockNumber)
    {
        $db = Database::GetInstance();
        $lastGenerationNumber = Generation::originNumber($lastBlockNumber);
        $lastBunchNumber = Generation::originNumber($lastGenerationNumber) + Rule::BUNCH;

        do {
            $lastBunchNumber = Rule::bunchFinalNumber($lastBunchNumber - Rule::BUNCH);
            File::rrmdir(self::txFullDir($lastBunchNumber));

            if (is_file(self::txArchive($lastBunchNumber))) {
                unlink(self::txArchive($lastBunchNumber));
            }
        } while ($lastBunchNumber > Rule::BUNCH);

        do {
            $lastGenerationNumber = Generation::originNumber($lastGenerationNumber);
            $query = ['block_number' => ['$lt' => $lastGenerationNumber]];
            $blocks = Block::datas(MongoDbConfig::NAMESPACE_BLOCK, Rule::GENERATION, $query);

            $blockhashs = [];

            foreach ($blocks as $block) {
                $blockhashs[] = $block['blockhash'];
            }

            if (count($blockhashs) > 0) {
                $db->bulk->delete(['block' => ['$in' => $blockhashs]]);
                $db->BulkWrite(MongoDbConfig::NAMESPACE_TRANSACTION);

                $db->bulk->delete(['blockhash' => ['$in' => $blockhashs]]);
                $db->BulkWrite(MongoDbConfig::NAMESPACE_BLOCK);
            }
        } while ($lastGenerationNumber > Rule::GENERATION);
    }
}
