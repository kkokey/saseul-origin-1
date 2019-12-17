<?php

namespace Saseul\Util;

/**
 * RestCall provides functions for HTTP request and etc.
 */
class RestCall
{
    protected static $instance = null;

    protected $rest;
    protected $timeout;
    protected $info;
    protected $multiObj;

    public function __construct($timeout = 15)
    {
        $this->timeout = $timeout;
        $this->info = null;
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function multiPOST(array $hosts, string $urlPath = '', $data = [], $ssl = false, $header = [], int $timeout = 1): array
    {
        if (count($hosts) > 1000) {
            # TODO: Hmm..
            return [];
        }

        $this->multiObj = [];
        $multiRest = curl_multi_init();
        $obj = $this;

        foreach ($hosts as $k => $host) {
            if (!is_string($host)) {
                continue;
            }

            $c = curl_init();

            curl_setopt($c, CURLOPT_URL, "http://{$host}/{$urlPath}");
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, $ssl);
            curl_setopt($c, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($c, CURLOPT_POST, true);

            if (is_array($data)) {
                curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($c, CURLOPT_POSTFIELDS, $data);
            }

            if (count($header) > 0) {
                curl_setopt($c, CURLOPT_HTTPHEADER, $header);
            }

            curl_multi_add_handle($multiRest, $c);
            $obj->multiObj[] = ['c' => $c];
        }

        do {
            $status = curl_multi_exec($multiRest, $active);
            if ($active) {
                curl_multi_select($multiRest);
            }
        } while ($active && $status == CURLM_OK);

        foreach ($this->multiObj as $k => $item) {
            $info = curl_getinfo($item['c']);

            $this->multiObj[$k]['result'] = curl_multi_getcontent($item['c']);
            $this->multiObj[$k]['host'] = preg_replace("/http:\/\/(.*?)\/.*/", '$1', $info['url']);
            $this->multiObj[$k]['exec_time'] = $info['total_time'];

            curl_multi_remove_handle($multiRest, $item['c']);
            unset($this->multiObj[$k]['c']);
        }

        curl_multi_close($multiRest);

        return $this->multiObj;
    }

    /**
     *  Requests an http response using the GET method with the given URL.
     *
     * @param string $url The URL address to send the request to.
     * @param bool $ssl If true, verifying the peer's certificate.
     * @param array $header The keys and values to include in the http header.
     *
     * @return bool|string true on success or false on failure.
     *                     However, if the CURLOPT_RETURNTRANSFER option is set,
     *                     it will return the result on success, false on failure.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.1
     * @see https://php.net/manual/en/function.curl-exec.php
     */
    public function get($url, $ssl = false, $header = [])
    {
        $this->rest = curl_init();

        curl_setopt($this->rest, CURLOPT_URL, $url);
        curl_setopt($this->rest, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->rest, CURLOPT_SSL_VERIFYPEER, $ssl);
        curl_setopt($this->rest, CURLOPT_TIMEOUT, $this->timeout);

        if (count($header) > 0) {
            curl_setopt($this->rest, CURLOPT_HTTPHEADER, $header);
        }

        $returnVal = curl_exec($this->rest);
        $this->info = curl_getinfo($this->rest);
        curl_close($this->rest);

        return $returnVal;
    }

    /**
     *  Requests an HTTP response using the POST method with the given URL
     *  and data.
     *
     * @param string $url The url address to send the request to.
     * @param array $data The data to attach to the request.
     * @param bool $ssl If true, verifying the peer's certificate.
     * @param array $header The keys and values to include in the http header.
     *
     * @return bool|string true on success or false on failure.
     *                     However, if the CURLOPT_RETURNTRANSFER option is set,
     *                     it will return the result on success, false on failure.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.3.3
     * @see https://php.net/manual/en/function.curl-exec.php
     */
    public function post($url, $data = [], $ssl = false, $header = [])
    {
        $this->rest = curl_init();

        curl_setopt($this->rest, CURLOPT_URL, $url);
        curl_setopt($this->rest, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->rest, CURLOPT_SSL_VERIFYPEER, $ssl);
        curl_setopt($this->rest, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->rest, CURLOPT_POST, true);

        if (is_array($data)) {
            curl_setopt($this->rest, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($this->rest, CURLOPT_POSTFIELDS, $data);
        }

        if (count($header) > 0) {
            curl_setopt($this->rest, CURLOPT_HTTPHEADER, $header);
        }

        $returnVal = curl_exec($this->rest);
        $this->info = curl_getinfo($this->rest);
        curl_close($this->rest);

        return $returnVal;
    }
}
