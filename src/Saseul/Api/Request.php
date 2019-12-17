<?php

namespace Saseul\Api;

use Saseul\Committer\RequestManager;
use Saseul\Common\Api;
use Saseul\Core\Rule;

class Request extends Api
{
    protected $request_manager;

    protected $request;
    protected $public_key;
    protected $signature;

    public function __construct()
    {
        $this->request_manager = new RequestManager();
    }

    public function main()
    {
        $this->request = json_decode($this->getParam($_REQUEST, 'request', ['default' => '{}']), true);
        $this->public_key = $this->getParam($_REQUEST, 'public_key', ['default' => '']);
        $this->signature = $this->getParam($_REQUEST, 'signature', ['default' => '']);

        $type = $this->getParam($this->request, 'type');
        $request = $this->request;
        $rhash = Rule::hash($request);
        $public_key = $this->public_key;
        $signature = $this->signature;

        $this->request_manager->initializeRequest($type, $request, $rhash, $public_key, $signature);
        $validity = $this->request_manager->getRequestValidity();

        if ($validity == false) {
            $this->Error('Invalid request');
        }

        $this->data = $this->request_manager->getResponse();
    }
}
