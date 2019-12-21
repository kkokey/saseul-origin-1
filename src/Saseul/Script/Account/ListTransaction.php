<?php

namespace Saseul\Script\Account;

use Saseul\Common\Script;
use Saseul\Core\Rule;
use Saseul\Data\Tracker;
use Saseul\Util\DateTime;
use Saseul\Util\Logger;
use Saseul\Util\RestCall;
use Saseul\Version;

class ListTransaction extends Script
{
    function main()
    {
        $validator = Tracker::getRandomValidator();
        $host = $validator['host'] ?? '';
        $rest = RestCall::GetInstance();

        $items = [];
        $items[] = $this->item1();

        foreach ($items as $item) {
            $rs = $rest->post('http://'.$host.'/request', $item);
            Logger::log($host);
            Logger::log($rs);
        }
    }

    function item1(int $page = 1, int $count = 20, int $sort = -1)
    {
        $type = 'ListTransaction';
        $request = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => '',
            'timestamp' => DateTime::microtime() + Rule::MICROINTERVAL_OF_CHUNK,
            'page' => $page,
            'count' => $count,
            'sort' => $sort,
        ];

        $item = [
            'request' => json_encode($request),
            'rhash' => '',
            'public_key' => '',
            'signature' => ''
        ];

        return $item;
    }
}
